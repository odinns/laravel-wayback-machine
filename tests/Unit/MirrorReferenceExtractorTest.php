<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Odinns\LaravelWaybackMachine\CdxCapture;
use Odinns\LaravelWaybackMachine\DownloadResult;
use Odinns\LaravelWaybackMachine\MirrorReferenceExtractor;

it('extracts absolute references from html and css files', function (): void {
    $directory = sys_get_temp_dir().'/wayback-reference-extract-'.bin2hex(random_bytes(4));
    mkdir($directory, 0777, true);

    $html = $directory.'/index.html';
    $css = $directory.'/app.css';
    file_put_contents($html, '<body background=goldback.gif><img src=images/os-59.jpg><img src="/cgi-bin/nph-count?width=8"><link href="/app.css"><a href="/download.zip">x</a><a href="mailto:test@example.com">x</a>');
    file_put_contents($css, 'body{background:url("../hero.jpg")}');

    $references = (new MirrorReferenceExtractor(new Filesystem()))->extract([
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/', 200, 'text/html', null, null), $html, 'downloaded'),
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/assets/app.css', 200, 'text/css', null, null), $css, 'downloaded'),
    ]);

    expect($references)->toContain('https://example.com/images/os-59.jpg')
        ->and($references)->toContain('https://example.com/app.css')
        ->and($references)->toContain('https://example.com/hero.jpg')
        ->and($references)->toContain('https://example.com/goldback.gif')
        ->and($references)->not->toContain('https://example.com/cgi-bin/nph-count?width=8')
        ->and($references)->not->toContain('https://example.com/download.zip')
        ->and($references)->not->toContain('mailto:test@example.com');

    (new Filesystem())->deleteDirectory($directory);
});
