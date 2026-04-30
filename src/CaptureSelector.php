<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

final class CaptureSelector
{
    /**
     * @param  list<CdxCapture>  $captures
     * @return list<CdxCapture>
     */
    public static function select(array $captures, string $selection): array
    {
        return match ($selection) {
            'all' => $captures,
            'unique-content' => self::uniqueContent($captures),
            default => self::latestPerUrl($captures),
        };
    }

    /**
     * @param  list<CdxCapture>  $captures
     * @return list<CdxCapture>
     */
    private static function latestPerUrl(array $captures): array
    {
        $selected = [];

        foreach ($captures as $capture) {
            $key = self::normalizeUrl($capture->originalUrl ?? '');
            $current = $selected[$key] ?? null;

            if (! $current instanceof CdxCapture || (string) $capture->timestamp > (string) $current->timestamp) {
                $selected[$key] = $capture;
            }
        }

        return array_values($selected);
    }

    /**
     * @param  list<CdxCapture>  $captures
     * @return list<CdxCapture>
     */
    private static function uniqueContent(array $captures): array
    {
        $selected = [];

        foreach ($captures as $capture) {
            $key = $capture->digest ?? self::normalizeUrl($capture->originalUrl ?? '').'|'.$capture->timestamp;

            $selected[$key] ??= $capture;
        }

        return array_values($selected);
    }

    private static function normalizeUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$port.$path.$query;
    }
}
