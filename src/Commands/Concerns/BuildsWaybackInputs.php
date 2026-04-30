<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine\Commands\Concerns;

use Odinns\LaravelWaybackMachine\CaptureScope;
use Odinns\LaravelWaybackMachine\CdxQuery;
use Odinns\LaravelWaybackMachine\ReplayUrlBuilder;
use Odinns\LaravelWaybackMachine\WaybackOptions;

trait BuildsWaybackInputs
{
    protected function scopeFromInput(string $argument = 'scope'): CaptureScope
    {
        return CaptureScope::from((string) $this->argument($argument), (string) $this->option('match'));
    }

    protected function queryFromOptions(): CdxQuery
    {
        $selection = (string) $this->option('selection');
        $collapse = $this->option('collapse');

        if ($collapse === null || $collapse === '') {
            $collapse = match ($selection) {
                'unique-content' => 'digest',
                default => null,
            };
        }

        return new CdxQuery(
            from: $this->nullableOption('from'),
            to: $this->nullableOption('to'),
            statuses: array_map(intval(...), (array) $this->option('status')),
            mimeTypes: array_values((array) $this->option('mime')),
            includePatterns: array_values((array) $this->option('include')),
            excludePatterns: array_values((array) $this->option('exclude')),
            collapse: is_string($collapse) ? $collapse : null,
            limit: $this->nullableIntOption('limit'),
            pageLimit: $this->nullableIntOption('page-limit'),
        );
    }

    protected function optionsFromInput(): WaybackOptions
    {
        app(ReplayUrlBuilder::class)->useReplayRoot($this->nullableOption('replay-root'));
        $ignoreErrors = (bool) $this->option('ignore-errors');

        return new WaybackOptions(
            timeout: (int) $this->option('timeout'),
            delayMs: (int) $this->option('delay-ms'),
            userAgent: (string) $this->option('user-agent'),
            followRedirects: (bool) $this->option('follow-redirects'),
            ignoreErrors: $ignoreErrors,
            force: (bool) $this->option('force'),
            dryRun: (bool) $this->option('dry-run'),
            selection: (string) $this->option('selection'),
            retryBackoffMs: $ignoreErrors ? [] : (array) config('wayback-machine.retry_backoff_ms', [1000, 3000, 10000, 30000]),
        );
    }

    protected function nullableOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function nullableIntOption(string $name): ?int
    {
        $value = $this->option($name);

        return $value === null || $value === '' ? null : (int) $value;
    }

    protected function sharedOptions(): string
    {
        return '{--match=host : Scope match mode: host, prefix, or exact}
            {--from= : Start timestamp/date}
            {--to= : End timestamp/date}
            {--status=* : Status code filter}
            {--mime=* : MIME type filter}
            {--include=* : Include URL pattern}
            {--exclude=* : Exclude URL pattern}
            {--selection=latest-per-url : latest-per-url, unique-content, or all}
            {--collapse= : Raw CDX collapse parameter}
            {--limit= : CDX limit}
            {--page-limit= : CDX page cap}
            {--delay-ms=2000 : Minimum global delay between Wayback requests}
            {--timeout=60 : HTTP timeout in seconds}
            {--user-agent=odinns/laravel-wayback-machine (+https://github.com/odinns/laravel-wayback-machine) : User-Agent}
            {--replay-root= : Custom replay root}
            {--follow-redirects : Follow replay redirects}
            {--ignore-errors : Skip failed downloads}
            {--dry-run : Plan without writing downloads}
            {--json : Emit JSON}
            {--progress : Show progress}
            {--force : Overwrite existing files}
            {--output= : Output path}';
    }
}
