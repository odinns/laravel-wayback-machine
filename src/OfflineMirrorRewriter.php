<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

use Illuminate\Filesystem\Filesystem;

final readonly class OfflineMirrorRewriter
{
    public function __construct(
        private Filesystem $files,
    ) {}

    /**
     * @param  list<DownloadResult>  $results
     */
    public function rewrite(array $results): void
    {
        $map = $this->urlMap($results);

        foreach ($results as $result) {
            if ($result->status === 'failed' || ! $this->files->isFile($result->path)) {
                continue;
            }

            if ($this->isHtml($result->capture)) {
                $this->files->put($result->path, $this->rewriteHtml($this->files->get($result->path), $result, $map));

                continue;
            }

            if ($this->isCss($result->capture)) {
                $this->files->put($result->path, $this->rewriteCss($this->files->get($result->path), $result, $map));
            }
        }
    }

    /**
     * @param  list<DownloadResult>  $results
     * @return array<string, string>
     */
    private function urlMap(array $results): array
    {
        $map = [];

        foreach ($results as $result) {
            if ($result->capture->originalUrl === null || $result->status === 'failed') {
                continue;
            }

            $map[$this->normalizeUrl($result->capture->originalUrl)] = $result->path;
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $map
     */
    private function rewriteHtml(string $html, DownloadResult $result, array $map): string
    {
        $baseUrl = (string) $result->capture->originalUrl;

        return (string) preg_replace_callback(
            '/\b(?<attr>href|src|srcset|action|background)=(?:(?<quote>[\'"])(?<quoted>[^\'"]+)\k<quote>|(?<unquoted>[^\s>]+))/i',
            function (array $match) use ($baseUrl, $result, $map): string {
                $quote = (string) ($match['quote'] ?? '');
                $value = $quote !== '' ? (string) $match['quoted'] : (string) $match['unquoted'];
                $rewritten = strtolower((string) $match['attr']) === 'srcset'
                    ? $this->rewriteSrcset($value, $baseUrl, $result->path, $map)
                    : $this->rewriteReference($value, $baseUrl, $result->path, $map);

                return $match['attr'].'='.$quote.$rewritten.$quote;
            },
            $html,
        );
    }

    /**
     * @param  array<string, string>  $map
     */
    private function rewriteCss(string $css, DownloadResult $result, array $map): string
    {
        return (string) preg_replace_callback(
            '/url\((?<quote>[\'"]?)(?<value>[^)\'"]+)(?<quote2>[\'"]?)\)/i',
            fn (array $match): string => 'url('.$match['quote'].$this->rewriteReference(
                trim((string) $match['value']),
                (string) $result->capture->originalUrl,
                $result->path,
                $map,
            ).$match['quote2'].')',
            $css,
        );
    }

    /**
     * @param  array<string, string>  $map
     */
    private function rewriteSrcset(string $srcset, string $baseUrl, string $fromPath, array $map): string
    {
        $candidates = array_map(trim(...), explode(',', $srcset));

        $rewritten = array_map(function (string $candidate) use ($baseUrl, $fromPath, $map): string {
            if ($candidate === '') {
                return $candidate;
            }

            $parts = preg_split('/\s+/', $candidate, 2);
            $url = $parts[0] ?? '';
            $descriptor = $parts[1] ?? null;
            $reference = $this->rewriteReference($url, $baseUrl, $fromPath, $map);

            return $descriptor === null ? $reference : $reference.' '.$descriptor;
        }, $candidates);

        return implode(', ', $rewritten);
    }

    /**
     * @param  array<string, string>  $map
     */
    private function rewriteReference(string $reference, string $baseUrl, string $fromPath, array $map): string
    {
        if ($this->shouldLeaveAlone($reference)) {
            return $reference;
        }

        $absolute = $this->absoluteUrl($reference, $baseUrl);

        if ($absolute === null) {
            return $reference;
        }

        $target = $map[$this->normalizeUrl($absolute)] ?? null;

        if ($target === null) {
            return $reference;
        }

        return $this->relativePath(dirname($fromPath), $target);
    }

    private function absoluteUrl(string $reference, string $baseUrl): ?string
    {
        if (str_starts_with($reference, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME);

            return (is_string($scheme) ? $scheme : 'https').':'.$reference;
        }

        if (parse_url($reference, PHP_URL_SCHEME) !== null) {
            return $reference;
        }

        $base = parse_url($baseUrl);

        if (! is_array($base) || ! isset($base['host'])) {
            return null;
        }

        $scheme = (string) ($base['scheme'] ?? 'https');
        $host = (string) $base['host'];
        $port = isset($base['port']) ? ':'.$base['port'] : '';

        if (str_starts_with($reference, '/')) {
            return $scheme.'://'.$host.$port.$reference;
        }

        $directory = preg_replace('#/[^/]*$#', '/', (string) ($base['path'] ?? '/')) ?: '/';

        return $this->removeDotSegments($scheme.'://'.$host.$port.$directory.$reference);
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
        $path = $this->normalizePath((string) ($parts['path'] ?? '/'));
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$port.$path.$query;
    }

    private function normalizePath(string $path): string
    {
        $normalized = parse_url($this->removeDotSegments('http://example.test/'.$path), PHP_URL_PATH);

        return is_string($normalized) && $normalized !== '' ? $normalized : '/';
    }

    private function removeDotSegments(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $segments = [];

        foreach (explode('/', (string) ($parts['path'] ?? '/')) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = '/'.implode('/', $segments);
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.$host.$port.$path.$query;
    }

    private function relativePath(string $fromDirectory, string $target): string
    {
        $from = explode(DIRECTORY_SEPARATOR, trim($fromDirectory, DIRECTORY_SEPARATOR));
        $to = explode(DIRECTORY_SEPARATOR, trim($target, DIRECTORY_SEPARATOR));

        while ($from !== [] && $to !== [] && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        return implode('/', array_merge(array_fill(0, count($from), '..'), $to)) ?: './';
    }

    private function shouldLeaveAlone(string $reference): bool
    {
        $reference = trim($reference);

        return $reference === ''
            || str_starts_with($reference, '#')
            || str_starts_with($reference, 'mailto:')
            || str_starts_with($reference, 'tel:')
            || str_starts_with($reference, 'data:')
            || str_starts_with($reference, 'javascript:');
    }

    private function isHtml(CdxCapture $capture): bool
    {
        return str_contains(strtolower((string) $capture->mimeType), 'html');
    }

    private function isCss(CdxCapture $capture): bool
    {
        return strtolower((string) $capture->mimeType) === 'text/css';
    }
}
