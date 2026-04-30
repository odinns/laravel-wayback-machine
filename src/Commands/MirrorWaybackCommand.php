<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine\Commands;

use Illuminate\Console\Command;
use Odinns\LaravelWaybackMachine\Commands\Concerns\BuildsWaybackInputs;
use Odinns\LaravelWaybackMachine\WaybackMirror;

final class MirrorWaybackCommand extends Command
{
    use BuildsWaybackInputs;

    protected $signature = 'wayback:mirror {scope} {--match=host} {--from=} {--to=} {--status=*} {--mime=*} {--include=*} {--exclude=*} {--selection=latest-per-url} {--collapse=} {--limit=} {--page-limit=} {--delay-ms=2000} {--timeout=60} {--user-agent=odinns/laravel-wayback-machine (+https://github.com/odinns/laravel-wayback-machine)} {--replay-root=} {--follow-redirects} {--ignore-errors} {--dry-run} {--json} {--progress} {--force} {--output=}';

    protected $description = 'Download a scoped Wayback Machine mirror from CDX captures.';

    public function handle(WaybackMirror $mirror): int
    {
        $query = $this->queryFromOptions();

        if ($query->limit === null && (app()->runningUnitTests() || ! $this->input->isInteractive())) {
            $this->error('Refusing an unbounded mirror in non-interactive mode. Add --limit.');

            return self::FAILURE;
        }

        if ($query->limit === null && ! $this->confirm('This mirror is unbounded. Continue?')) {
            return self::FAILURE;
        }

        $scope = $this->scopeFromInput();
        $output = $this->nullableOption('output') ?? (string) config('wayback-machine.paths.captures');
        $results = $mirror->mirror($scope, $query, $output, $this->optionsFromInput());

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
