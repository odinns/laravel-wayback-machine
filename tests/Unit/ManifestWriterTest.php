<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Odinns\LaravelWaybackMachine\CaptureManifest;
use Odinns\LaravelWaybackMachine\CaptureScope;
use Odinns\LaravelWaybackMachine\CdxCapture;
use Odinns\LaravelWaybackMachine\ManifestWriter;
use Odinns\LaravelWaybackMachine\ReplayUrlBuilder;

it('writes stable json manifests', function (): void {
    $path = sys_get_temp_dir().'/wayback-manifest-test.json';
    @unlink($path);

    $writer = new ManifestWriter(new Filesystem(), new ReplayUrlBuilder('https://web.archive.test'));
    $writer->write(new CaptureManifest(CaptureScope::from('example.com'), [
        new CdxCapture('20200101000000', 'https://example.com', 200, 'text/html', 'ABC', 10),
    ]), $path);

    expect(file_get_contents($path))->toContain('"raw_url": "https://web.archive.test/web/20200101000000id_/https://example.com"');
});
