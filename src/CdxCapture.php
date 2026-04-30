<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

use InvalidArgumentException;

final readonly class CdxCapture
{
    public const array DEFAULT_FIELDS = ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'];

    public function __construct(
        public ?string $timestamp,
        public ?string $originalUrl,
        public ?int $status,
        public ?string $mimeType,
        public ?string $digest,
        public ?int $length,
        public ?string $localPath = null,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            self::nullableString($row['timestamp'] ?? null),
            self::nullableString($row['original'] ?? null),
            self::nullableInt($row['statuscode'] ?? null),
            self::nullableString($row['mimetype'] ?? null),
            self::nullableString($row['digest'] ?? null),
            self::nullableInt($row['length'] ?? null),
        );
    }

    public function withLocalPath(string $localPath): self
    {
        return new self($this->timestamp, $this->originalUrl, $this->status, $this->mimeType, $this->digest, $this->length, $localPath);
    }

    public function withOriginalUrl(string $originalUrl): self
    {
        return new self($this->timestamp, $originalUrl, $this->status, $this->mimeType, $this->digest, $this->length, $this->localPath);
    }

    /**
     * @return array<string, mixed>
     */
    public function toManifestArray(ReplayUrlBuilder $urls): array
    {
        $this->assertDownloadable();

        return [
            'timestamp' => $this->timestamp,
            'original_url' => $this->originalUrl,
            'replay_url' => $urls->toolbar($this),
            'raw_url' => $urls->raw($this),
            'status' => $this->status,
            'mime' => $this->mimeType,
            'digest' => $this->digest,
            'length' => $this->length,
            'local_path' => $this->localPath,
        ];
    }

    public function assertDownloadable(): void
    {
        if ($this->timestamp === null || $this->originalUrl === null) {
            throw new InvalidArgumentException('Capture is missing timestamp or original URL.');
        }
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '-') {
            return null;
        }

        return (string) $value;
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '-' || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
