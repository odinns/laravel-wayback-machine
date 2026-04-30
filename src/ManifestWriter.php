<?php

declare(strict_types=1);

namespace Odinns\LaravelWaybackMachine;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

final readonly class ManifestWriter
{
    public function __construct(
        private Filesystem $files,
        private ReplayUrlBuilder $urls,
    ) {}

    public function write(CaptureManifest $manifest, string $path): string
    {
        $directory = dirname($path);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $json = json_encode($manifest->toArray($this->urls), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! is_string($json)) {
            throw new RuntimeException('Could not encode capture manifest.');
        }

        $this->files->put($path, $json.PHP_EOL);

        return $path;
    }
}
