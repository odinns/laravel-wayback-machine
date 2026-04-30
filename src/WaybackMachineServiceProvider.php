<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

use Illuminate\Support\ServiceProvider;
use Odinns\LaravelWaybackMachine\Commands\DownloadWaybackCommand;
use Odinns\LaravelWaybackMachine\Commands\ListWaybackCommand;
use Odinns\LaravelWaybackMachine\Commands\ManifestWaybackCommand;
use Odinns\LaravelWaybackMachine\Commands\MirrorWaybackCommand;
use Odinns\LaravelWaybackMachine\Support\GlobalRequestDelay;

final class WaybackMachineServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/wayback-machine.php', 'wayback-machine');

        $this->app->singleton(GlobalRequestDelay::class, fn (): GlobalRequestDelay => new GlobalRequestDelay(
            (int) config('wayback-machine.delay_ms', 2000),
        ));

        $this->app->singleton(WaybackClient::class);
        $this->app->singleton(WaybackDownloader::class);
        $this->app->singleton(WaybackMirror::class);
        $this->app->singleton(OfflineMirrorRewriter::class);
        $this->app->singleton(MirrorReferenceExtractor::class);
        $this->app->singleton(ManifestWriter::class);
        $this->app->singleton(ReplayUrlBuilder::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/wayback-machine.php' => config_path('wayback-machine.php'),
        ], 'wayback-machine-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ListWaybackCommand::class,
                ManifestWaybackCommand::class,
                DownloadWaybackCommand::class,
                MirrorWaybackCommand::class,
            ]);
        }
    }
}
