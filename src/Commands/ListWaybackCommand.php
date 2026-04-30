<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine\Commands;

use Illuminate\Console\Command;
use Odinns\LaravelWaybackMachine\Commands\Concerns\BuildsWaybackInputs;
use Odinns\LaravelWaybackMachine\ReplayUrlBuilder;
use Odinns\LaravelWaybackMachine\WaybackClient;

final class ListWaybackCommand extends Command
{
    use BuildsWaybackInputs;

    protected $signature = 'wayback:list {scope} {--match=host} {--from=} {--to=} {--status=*} {--mime=*} {--include=*} {--exclude=*} {--selection=latest-per-url} {--collapse=} {--limit=} {--page-limit=} {--delay-ms=2000} {--timeout=60} {--user-agent=odinns/laravel-wayback-machine (+https://github.com/odinns/laravel-wayback-machine)} {--replay-root=} {--follow-redirects} {--ignore-errors} {--dry-run} {--json} {--progress} {--force} {--output=}';

    protected $description = 'List matching Wayback Machine CDX captures.';

    public function handle(WaybackClient $client, ReplayUrlBuilder $urls): int
    {
        $captures = $client->captures($this->scopeFromInput(), $this->queryFromOptions(), $this->optionsFromInput());

        if ($this->option('json')) {
            $this->line((string) json_encode(array_map(fn ($capture): array => $capture->toManifestArray($urls), $captures), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        foreach ($captures as $capture) {
            $this->line(sprintf('%s %s %s %s', $capture->timestamp ?? '-', $capture->status ?? '-', $capture->mimeType ?? '-', $capture->originalUrl ?? '-'));
        }

        return self::SUCCESS;
    }
}
