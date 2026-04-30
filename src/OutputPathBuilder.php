<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

final class OutputPathBuilder
{
    public function capturePath(CaptureScope $scope, CdxCapture $capture, string $basePath): string
    {
        $capture->assertDownloadable();

        return $this->pathForTimestamp($scope, (string) $capture->timestamp, (string) $capture->originalUrl, $basePath);
    }

    public function dependencyPath(CaptureScope $scope, CdxCapture $source, CdxCapture $dependency, string $basePath): string
    {
        $source->assertDownloadable();
        $dependency->assertDownloadable();

        return $this->pathForTimestamp($scope, (string) $source->timestamp, (string) $dependency->originalUrl, $basePath);
    }

    private function pathForTimestamp(CaptureScope $scope, string $timestamp, string $url, string $basePath): string
    {
        return rtrim($basePath, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$scope->safeName()
            .DIRECTORY_SEPARATOR.$timestamp
            .DIRECTORY_SEPARATOR.$this->safeUrlPath($url);
    }

    public function manifestPath(CaptureScope $scope, string $basePath): string
    {
        return rtrim($basePath, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.$scope->safeName().'-'.now()->format('YmdHis').'.json';
    }

    private function safeUrlPath(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return sha1($url).'.bin';
        }

        $host = $this->safeSegment((string) ($parts['host'] ?? 'unknown-host'));
        $port = isset($parts['port']) ? '--port-'.$parts['port'] : '';
        $path = $this->safePath((string) ($parts['path'] ?? ''));
        $query = isset($parts['query']) ? '--query-'.substr(sha1((string) $parts['query']), 0, 12) : '';

        $candidate = $host.$port.'/'.$path.$query;

        return trim((string) preg_replace('/[^A-Za-z0-9._\/-]+/', '-', $candidate), '/');
    }

    private function safePath(string $path): string
    {
        $segments = array_filter(
            explode('/', trim($path, '/')),
            fn (string $segment): bool => $segment !== '' && $segment !== '.' && $segment !== '..',
        );

        $segments = array_map($this->safeSegment(...), $segments);

        return $segments === [] ? 'index.html' : implode('/', $segments);
    }

    private function safeSegment(string $segment): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $segment), '.-') ?: 'segment';
    }
}
