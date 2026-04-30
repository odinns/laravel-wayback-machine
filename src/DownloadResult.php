<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

final readonly class DownloadResult
{
    public function __construct(
        public CdxCapture $capture,
        public string $path,
        public string $status,
    ) {}
}
