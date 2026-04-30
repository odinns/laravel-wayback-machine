<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Odinns\LaravelWaybackMachine\Support\GlobalRequestDelay;

final readonly class WaybackClient
{
    public function __construct(
        private Factory $http,
        private CdxParser $parser,
        private ReplayUrlBuilder $urls,
        private GlobalRequestDelay $delay,
    ) {}

    /**
     * @return list<CdxCapture>
     */
    public function captures(CaptureScope $scope, CdxQuery $query, ?WaybackOptions $options = null): array
    {
        $options ??= WaybackOptions::fromConfig();
        $captures = [];
        $page = 0;
        $resumeKey = null;

        while (true) {
            if ($query->pageLimit !== null && $page >= $query->pageLimit) {
                break;
            }

            $cdxPage = $this->capturedPage($scope, $query, $page, $resumeKey, $options);

            if ($cdxPage->captures === []) {
                break;
            }

            array_push($captures, ...$this->filterCaptures($cdxPage->captures, $query));
            $page++;
            $resumeKey = $cdxPage->resumeKey;

            if ($resumeKey === null) {
                break;
            }
        }

        return CaptureSelector::select($captures, $options->selection);
    }

    public function download(CdxCapture $capture, ?WaybackOptions $options = null): string
    {
        $options ??= WaybackOptions::fromConfig();
        $this->delay->setDelayMs($options->delayMs);
        $this->delay->wait();

        return $this->request($options)
            ->withOptions(['allow_redirects' => $options->followRedirects])
            ->get($this->urls->raw($capture))
            ->throw()
            ->body();
    }

    /**
     */
    private function capturedPage(CaptureScope $scope, CdxQuery $query, int $page, ?string $resumeKey, WaybackOptions $options): CdxPage
    {
        $this->delay->setDelayMs($options->delayMs);
        $this->delay->wait();

        if ($options->ignoreErrors) {
            $response = rescue(
                fn (): Response => $this->request($options)
                    ->get((string) config('wayback-machine.cdx_endpoint'), $query->parametersFor($scope, $page, $resumeKey)),
                report: false,
            );

            if (! $response instanceof Response) {
                return new CdxPage([], null);
            }
        } else {
            $response = $this->request($options)
                ->get((string) config('wayback-machine.cdx_endpoint'), $query->parametersFor($scope, $page, $resumeKey));
        }

        try {
            $response->throw();
        } catch (RequestException $exception) {
            if ($options->ignoreErrors) {
                return new CdxPage([], null);
            }

            throw $exception;
        }

        return $this->parser->parsePage($response->body());
    }

    private function request(WaybackOptions $options): PendingRequest
    {
        $request = $this->http
            ->timeout($options->timeout)
            ->acceptJson()
            ->withUserAgent($options->userAgent);

        if ($options->retryBackoffMs === []) {
            return $request;
        }

        return $request->retry(
                times: $options->retryBackoffMs,
                sleepMilliseconds: 0,
                when: fn (mixed $exception, mixed $request): bool => $this->shouldRetry($exception),
                throw: false,
            );
    }

    /**
     * @param  list<CdxCapture>  $captures
     * @return list<CdxCapture>
     */
    private function filterCaptures(array $captures, CdxQuery $query): array
    {
        return array_values(array_filter(
            $captures,
            fn (CdxCapture $capture): bool => $this->matchesQuery($capture, $query),
        ));
    }

    private function matchesQuery(CdxCapture $capture, CdxQuery $query): bool
    {
        if ($query->statuses !== [] && ! in_array($capture->status, $query->statuses, true)) {
            return false;
        }

        if ($query->mimeTypes !== [] && ! in_array($capture->mimeType, $query->mimeTypes, true)) {
            return false;
        }

        $url = (string) $capture->originalUrl;

        if ($query->includePatterns !== [] && ! $this->matchesAnyPattern($url, $query->includePatterns)) {
            return false;
        }

        return ! $this->matchesAnyPattern($url, $query->excludePatterns);
    }

    /**
     * @param  list<string>  $patterns
     */
    private function matchesAnyPattern(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $value, FNM_CASEFOLD) || @preg_match('~'.$pattern.'~i', $value) === 1) {
                return true;
            }
        }

        return false;
    }

    private function shouldRetry(mixed $exception): bool
    {
        if (! $exception instanceof RequestException) {
            return true;
        }

        return in_array($exception->response->status(), (array) config('wayback-machine.retry_statuses', []), true);
    }
}
