<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Odinns\LaravelWaybackMachine\CdxCapture;
use Odinns\LaravelWaybackMachine\CdxParser;
use Odinns\LaravelWaybackMachine\ReplayUrlBuilder;
use Odinns\LaravelWaybackMachine\Support\GlobalRequestDelay;
use Odinns\LaravelWaybackMachine\WaybackClient;
use Odinns\LaravelWaybackMachine\WaybackDownloader;
use Odinns\LaravelWaybackMachine\WaybackOptions;

it('redownloads existing files when cdx length shows they are partial', function (): void {
    $path = sys_get_temp_dir().'/wayback-partial-download-test.txt';
    file_put_contents($path, 'short');

    Http::fake([
        'web.archive.test/web/*' => Http::response('complete-content'),
    ]);

    $client = new WaybackClient(app(Factory::class), new CdxParser(), new ReplayUrlBuilder('https://web.archive.test'), new GlobalRequestDelay(0));
    $downloader = new WaybackDownloader($client, new Filesystem());

    $result = $downloader->download(
        new CdxCapture('20200101000000', 'https://example.com/file.txt', 200, 'text/plain', 'ABC', strlen('complete-content')),
        $path,
        new WaybackOptions(delayMs: 0, userAgent: 'test-agent'),
    );

    expect($result->status)->toBe('downloaded')
        ->and(file_get_contents($path))->toBe('complete-content');

    @unlink($path);
});
