<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine\Commands;

use Illuminate\Console\Command;
use Odinns\LaravelWaybackMachine\Commands\Concerns\BuildsWaybackInputs;
use Odinns\LaravelWaybackMachine\OutputPathBuilder;
use Odinns\LaravelWaybackMachine\WaybackClient;
use Odinns\LaravelWaybackMachine\WaybackDownloader;

final class DownloadWaybackCommand extends Command
{
    use BuildsWaybackInputs;

    protected $signature = 'wayback:download {url-or-scope} {--match=exact} {--from=} {--to=} {--status=*} {--mime=*} {--include=*} {--exclude=*} {--selection=latest-per-url} {--collapse=} {--limit=} {--page-limit=} {--delay-ms=2000} {--timeout=60} {--user-agent=odinns/laravel-wayback-machine (+https://github.com/odinns/laravel-wayback-machine)} {--replay-root=} {--follow-redirects} {--ignore-errors} {--dry-run} {--json} {--progress} {--force} {--output=}';

    protected $description = 'Download selected Wayback Machine captures.';

    public function handle(WaybackClient $client, WaybackDownloader $downloader, OutputPathBuilder $paths): int
    {
        $scope = $this->scopeFromInput('url-or-scope');
        $options = $this->optionsFromInput();
        $captures = $client->captures($scope, $this->queryFromOptions(), $options);
        $output = $this->nullableOption('output') ?? (string) config('wayback-machine.paths.captures');
        $results = [];

        foreach ($captures as $capture) {
            $results[] = $downloader->download($capture, $paths->capturePath($scope, $capture, $output), $options);
        }

        if ($this->option('json')) {
            $this->line((string) json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($results as $result) {
                $this->line($result->status.' '.$result->path);
            }
        }

        return self::SUCCESS;
    }
}
