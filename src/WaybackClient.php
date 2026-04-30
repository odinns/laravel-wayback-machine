<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
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

            array_push($captures, ...$cdxPage->captures);
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

        $response = $this->request($options)
            ->get((string) config('wayback-machine.cdx_endpoint'), $query->parametersFor($scope, $page, $resumeKey));

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
        return $this->http
            ->timeout($options->timeout)
            ->acceptJson()
            ->withUserAgent($options->userAgent)
            ->retry(
                times: $options->retryBackoffMs,
                sleepMilliseconds: 0,
                when: fn (mixed $exception, mixed $request): bool => $this->shouldRetry($exception),
                throw: false,
            );
    }

    private function shouldRetry(mixed $exception): bool
    {
        if (! $exception instanceof RequestException) {
            return true;
        }

        return in_array($exception->response->status(), (array) config('wayback-machine.retry_statuses', []), true);
    }
}
