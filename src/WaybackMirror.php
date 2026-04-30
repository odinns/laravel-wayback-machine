<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

final readonly class WaybackMirror
{
    public function __construct(
        private WaybackClient $client,
        private WaybackDownloader $downloader,
        private OutputPathBuilder $paths,
        private OfflineMirrorRewriter $rewriter,
        private MirrorReferenceExtractor $references,
    ) {}

    /**
     * @return list<DownloadResult>
     */
    public function mirror(CaptureScope $scope, CdxQuery $query, string $output, WaybackOptions $options): array
    {
        $captures = $this->client->captures($scope, $query, $options);
        $results = [];

        foreach ($captures as $capture) {
            $results[] = $this->downloader->download(
                $capture,
                $this->paths->capturePath($scope, $capture, $output),
                $options,
            );
        }

        if (! $options->dryRun) {
            $results = $this->downloadDependencies($scope, $query, $output, $options, $results);
        }

        if (! $options->dryRun) {
            $this->rewriter->rewrite($results);
        }

        return $results;
    }

    /**
     * @param  list<DownloadResult>  $results
     * @return list<DownloadResult>
     */
    private function downloadDependencies(CaptureScope $scope, CdxQuery $query, string $output, WaybackOptions $options, array $results): array
    {
        $known = [];

        foreach ($results as $result) {
            if ($result->capture->originalUrl !== null) {
                $known[$this->normalizeUrl($result->capture->originalUrl)] = true;
            }
        }

        for ($pass = 0; $pass < 3; $pass++) {
            $added = false;

            foreach ($results as $result) {
                if ($result->status === 'failed') {
                    continue;
                }

                foreach ($this->references->extract([$result]) as $url) {
                    if (! $this->belongsToScope($url, $scope)) {
                        continue;
                    }

                    $key = $this->normalizeUrl($url);

                    if (isset($known[$key]) || count($known) >= 500) {
                        continue;
                    }

                    $captures = $this->client->captures(CaptureScope::from($url, 'exact'), new CdxQuery(
                        from: $query->from,
                        to: $query->to,
                        statuses: [200],
                        limit: 10,
                        pageLimit: 1,
                    ), $options);
                    $capture = $this->selectDependencyCapture($url, $captures);

                    $known[$key] = true;

                    if (! $capture instanceof CdxCapture) {
                        continue;
                    }

                    $capture = $capture->withOriginalUrl($url);

                    $results[] = $this->downloader->download(
                        $capture,
                        $this->paths->dependencyPath($scope, $result->capture, $capture, $output),
                        $options,
                    );
                    $added = true;
                }
            }

            if (! $added) {
                break;
            }
        }

        return $results;
    }

    private function belongsToScope(string $url, CaptureScope $scope): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host)) {
            return false;
        }

        $scopeHost = parse_url($scope->value, PHP_URL_HOST);
        $scopeHost = is_string($scopeHost) ? $scopeHost : $scope->value;
        $scopeHost = preg_replace('#/.*$#', '', $scopeHost) ?? $scopeHost;

        return $host === $scopeHost || str_ends_with($host, '.'.$scopeHost);
    }

    /**
     * @param  list<CdxCapture>  $captures
     */
    private function selectDependencyCapture(string $url, array $captures): ?CdxCapture
    {
        if (! $this->looksLikeStaticAsset($url)) {
            return $captures[0] ?? null;
        }

        foreach ($captures as $capture) {
            if (! str_contains(strtolower((string) $capture->mimeType), 'html')) {
                return $capture;
            }
        }

        return null;
    }

    private function looksLikeStaticAsset(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && preg_match('/\.(?:avif|css|gif|ico|jpe?g|js|png|svg|webp|woff2?|ttf|otf)$/i', $path) === 1;
    }


    private function normalizeUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            return $url;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $host = strtolower((string) $parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$port.$path.$query;
    }
}
