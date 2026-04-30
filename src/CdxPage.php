<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

final readonly class CdxPage
{
    /**
     * @param  list<CdxCapture>  $captures
     */
    public function __construct(
        public array $captures,
        public ?string $resumeKey,
    ) {}
}
