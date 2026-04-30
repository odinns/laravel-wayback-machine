<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

use Illuminate\Filesystem\Filesystem;
use Throwable;

final readonly class WaybackDownloader
{
    public function __construct(
        private WaybackClient $client,
        private Filesystem $files,
    ) {}

    public function download(CdxCapture $capture, string $path, WaybackOptions $options): DownloadResult
    {
        $capture->assertDownloadable();

        if ($this->files->exists($path) && ! $options->force && $this->existingFileLooksComplete($capture, $path)) {
            return new DownloadResult($capture->withLocalPath($path), $path, 'skipped');
        }

        if ($options->dryRun) {
            return new DownloadResult($capture->withLocalPath($path), $path, 'planned');
        }

        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        try {
            $this->files->put($path, $this->client->download($capture, $options));
        } catch (Throwable $throwable) {
            if (! $options->ignoreErrors) {
                throw $throwable;
            }

            return new DownloadResult($capture->withLocalPath($path), $path, 'failed');
        }

        return new DownloadResult($capture->withLocalPath($path), $path, 'downloaded');
    }

    private function existingFileLooksComplete(CdxCapture $capture, string $path): bool
    {
        if ($capture->length === null) {
            return true;
        }

        return $this->files->size($path) === $capture->length;
    }
}
