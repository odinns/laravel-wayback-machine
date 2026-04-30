<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine\Support;

final class GlobalRequestDelay
{
    private float $nextAllowedAt = 0.0;

    public function __construct(
        private int $delayMs,
    ) {}

    public function setDelayMs(int $delayMs): void
    {
        $this->delayMs = max(0, $delayMs);
    }

    public function wait(): void
    {
        $now = microtime(true);

        if ($this->nextAllowedAt > $now) {
            usleep((int) (($this->nextAllowedAt - $now) * 1_000_000));
        }

        $this->nextAllowedAt = microtime(true) + ($this->delayMs / 1000);
    }
}
