<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

use Illuminate\Filesystem\Filesystem;

final readonly class MirrorReferenceExtractor
{
    public function __construct(
        private Filesystem $files,
    ) {}

    /**
     * @param  list<DownloadResult>  $results
     * @return list<string>
     */
    public function extract(array $results): array
    {
        $references = [];

        foreach ($results as $result) {
            if ($result->status === 'failed' || ! $this->files->isFile($result->path)) {
                continue;
            }

            if ($this->isHtml($result->capture)) {
                array_push($references, ...$this->extractHtml($this->files->get($result->path), (string) $result->capture->originalUrl));
            }

            if ($this->isCss($result->capture)) {
                array_push($references, ...$this->extractCss($this->files->get($result->path), (string) $result->capture->originalUrl));
            }
        }

        return array_values(array_unique(array_filter($references)));
    }

    /**
     * @return list<string>
     */
    private function extractHtml(string $html, string $baseUrl): array
    {
        preg_match_all('/<(?<tag>[a-z][a-z0-9:-]*)\b[^>]*\b(?<attr>href|src|srcset|background)=(?:(?<quote>[\'"])(?<quoted>[^\'"]+)\k<quote>|(?<unquoted>[^\s>]+))/i', $html, $matches);

        $urls = [];

        foreach ($matches['quoted'] as $index => $quoted) {
            if (! $this->shouldExtractHtmlAttribute((string) $matches['tag'][$index], (string) $matches['attr'][$index])) {
                continue;
            }

            $value = $quoted !== '' ? $quoted : $matches['unquoted'][$index];

            if (str_contains($value, ',')) {
                foreach (explode(',', $value) as $candidate) {
                    $parts = preg_split('/\s+/', trim($candidate), 2);
                    $urls[] = $this->absoluteUrl((string) ($parts[0] ?? ''), $baseUrl);
                }

                continue;
            }

            $urls[] = $this->absoluteUrl($value, $baseUrl);
        }

        return array_values(array_filter($urls));
    }

    /**
     * @return list<string>
     */
    private function extractCss(string $css, string $baseUrl): array
    {
        preg_match_all('/url\((?:[\'"]?)(?<value>[^)\'"]+)(?:[\'"]?)\)/i', $css, $matches);

        return array_values(array_filter(array_map(
            fn (string $value): ?string => $this->absoluteUrl(trim($value), $baseUrl),
            $matches['value'],
        )));
    }

    private function absoluteUrl(string $reference, string $baseUrl): ?string
    {
        if ($this->shouldLeaveAlone($reference)) {
            return null;
        }

        if (str_starts_with($reference, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME);

            return (is_string($scheme) ? $scheme : 'https').':'.$reference;
        }

        if (parse_url($reference, PHP_URL_SCHEME) !== null) {
            return $this->shouldSkipUrl($reference) ? null : $reference;
        }

        $base = parse_url($baseUrl);

        if (! is_array($base) || ! isset($base['host'])) {
            return null;
        }

        $scheme = (string) ($base['scheme'] ?? 'https');
        $host = (string) $base['host'];
        $port = isset($base['port']) ? ':'.$base['port'] : '';

        if (str_starts_with($reference, '/')) {
            $url = $scheme.'://'.$host.$port.$reference;

            return $this->shouldSkipUrl($url) ? null : $url;
        }

        $directory = preg_replace('#/[^/]*$#', '/', (string) ($base['path'] ?? '/')) ?: '/';

        $url = $this->removeDotSegments($scheme.'://'.$host.$port.$directory.$reference);

        return $this->shouldSkipUrl($url) ? null : $url;
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

    private function shouldExtractHtmlAttribute(string $tag, string $attribute): bool
    {
        $tag = strtolower($tag);
        $attribute = strtolower($attribute);

        return $attribute === 'src'
            || $attribute === 'srcset'
            || $attribute === 'background'
            || ($attribute === 'href' && $tag === 'link');
    }

    private function shouldSkipUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);

        return is_string($path) && str_starts_with($path, '/cgi-bin/');
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
