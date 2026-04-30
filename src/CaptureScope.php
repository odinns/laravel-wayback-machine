<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

use InvalidArgumentException;

final readonly class CaptureScope
{
    private function __construct(
        public string $value,
        public string $match,
    ) {}

    public static function from(string $value, string $match = 'host'): self
    {
        $value = trim($value);
        $match = strtolower(trim($match));

        if ($value === '') {
            throw new InvalidArgumentException('Capture scope cannot be empty.');
        }

        if (! in_array($match, ['host', 'prefix', 'exact'], true)) {
            throw new InvalidArgumentException('Capture scope match must be host, prefix, or exact.');
        }

        return new self(self::normalizeValue($value, $match), $match);
    }

    public function cdxUrl(): string
    {
        return match ($this->match) {
            'host' => $this->host().'*',
            'prefix' => rtrim($this->value, '*').'*',
            default => $this->value,
        };
    }

    public function safeName(): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $this->value), '-') ?: 'scope';
    }

    private function host(): string
    {
        $host = parse_url($this->value, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return $host;
        }

        $withoutScheme = preg_replace('#^https?://#', '', $this->value) ?? $this->value;

        return explode('/', $withoutScheme, 2)[0];
    }

    private static function normalizeValue(string $value, string $match): string
    {
        if ($match === 'host') {
            $host = parse_url($value, PHP_URL_HOST);

            return is_string($host) && $host !== '' ? $host : preg_replace('#^https?://#', '', $value) ?? $value;
        }

        if (! str_contains($value, '://')) {
            return 'https://'.$value;
        }

        return $value;
    }
}
