<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine\Commands;

use Illuminate\Console\Command;
use Odinns\LaravelWaybackMachine\CaptureManifest;
use Odinns\LaravelWaybackMachine\Commands\Concerns\BuildsWaybackInputs;
use Odinns\LaravelWaybackMachine\ManifestWriter;
use Odinns\LaravelWaybackMachine\OutputPathBuilder;
use Odinns\LaravelWaybackMachine\WaybackClient;

final class ManifestWaybackCommand extends Command
{
    use BuildsWaybackInputs;

    protected $signature = 'wayback:manifest {scope} {--match=host} {--from=} {--to=} {--status=*} {--mime=*} {--include=*} {--exclude=*} {--selection=latest-per-url} {--collapse=} {--limit=} {--page-limit=} {--delay-ms=2000} {--timeout=60} {--user-agent=odinns/laravel-wayback-machine (+https://github.com/odinns/laravel-wayback-machine)} {--replay-root=} {--follow-redirects} {--ignore-errors} {--dry-run} {--json} {--progress} {--force} {--output=}';

    protected $description = 'Write a JSON manifest for matching Wayback Machine captures.';

    public function handle(WaybackClient $client, ManifestWriter $writer, OutputPathBuilder $paths): int
    {
        $scope = $this->scopeFromInput();
        $captures = $client->captures($scope, $this->queryFromOptions(), $this->optionsFromInput());
        $path = $this->nullableOption('output') ?? $paths->manifestPath($scope, (string) config('wayback-machine.paths.manifests'));

        $writer->write(new CaptureManifest($scope, $captures), $path);
        $this->line($path);

        return self::SUCCESS;
    }
}
