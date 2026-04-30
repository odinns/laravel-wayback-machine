<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

final readonly class WaybackOptions
{
    /**
     * @param  list<int>  $retryBackoffMs
     */
    public function __construct(
        public int $timeout = 60,
        public int $delayMs = 2000,
        public string $userAgent = '',
        public bool $followRedirects = false,
        public bool $ignoreErrors = false,
        public bool $force = false,
        public bool $dryRun = false,
        public string $selection = 'latest-per-url',
        public array $retryBackoffMs = [1000, 3000, 10000, 30000],
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            timeout: (int) config('wayback-machine.timeout', 60),
            delayMs: (int) config('wayback-machine.delay_ms', 2000),
            userAgent: (string) config('wayback-machine.user_agent'),
            retryBackoffMs: (array) config('wayback-machine.retry_backoff_ms', [1000, 3000, 10000, 30000]),
        );
    }
}
