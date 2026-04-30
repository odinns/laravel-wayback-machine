<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Odinns\LaravelWaybackMachine\CdxCapture;
use Odinns\LaravelWaybackMachine\DownloadResult;
use Odinns\LaravelWaybackMachine\OfflineMirrorRewriter;

it('rewrites html references to downloaded local files', function (): void {
    $directory = sys_get_temp_dir().'/wayback-rewrite-'.bin2hex(random_bytes(4));
    mkdir($directory, 0777, true);
    mkdir($directory.'/assets', 0777, true);

    $index = $directory.'/index.html';
    $css = $directory.'/assets/app.css';
    file_put_contents($index, '<link href="/assets/app.css"><img src="https://example.com/assets/logo.png">');
    file_put_contents($css, 'body{}');
    file_put_contents($directory.'/assets/logo.png', 'png');

    (new OfflineMirrorRewriter(new Filesystem()))->rewrite([
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/', 200, 'text/html', null, null), $index, 'downloaded'),
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/assets/app.css', 200, 'text/css', null, null), $css, 'downloaded'),
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/assets/logo.png', 200, 'image/png', null, null), $directory.'/assets/logo.png', 'downloaded'),
    ]);

    expect(file_get_contents($index))->toBe('<link href="assets/app.css"><img src="assets/logo.png">');

    (new Filesystem())->deleteDirectory($directory);
});

it('rewrites unquoted html references', function (): void {
    $directory = sys_get_temp_dir().'/wayback-unquoted-rewrite-'.bin2hex(random_bytes(4));
    mkdir($directory.'/images', 0777, true);

    $index = $directory.'/index.html';
    $image = $directory.'/images/os-59.jpg';
    file_put_contents($index, '<a href=images/os-59.jpg><img src=images/os-59.jpg></a>');
    file_put_contents($image, 'jpg');

    (new OfflineMirrorRewriter(new Filesystem()))->rewrite([
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/', 200, 'text/html', null, null), $index, 'downloaded'),
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/images/os-59.jpg', 200, 'image/jpeg', null, null), $image, 'downloaded'),
    ]);

    expect(file_get_contents($index))->toBe('<a href=images/os-59.jpg><img src=images/os-59.jpg></a>');

    (new Filesystem())->deleteDirectory($directory);
});

it('rewrites unquoted html references across directories', function (): void {
    $directory = sys_get_temp_dir().'/wayback-nested-rewrite-'.bin2hex(random_bytes(4));
    mkdir($directory.'/pages', 0777, true);
    mkdir($directory.'/images', 0777, true);

    $page = $directory.'/pages/about.html';
    $image = $directory.'/images/os-59.jpg';
    file_put_contents($page, '<img src=/images/os-59.jpg>');
    file_put_contents($image, 'jpg');

    (new OfflineMirrorRewriter(new Filesystem()))->rewrite([
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/pages/about.html', 200, 'text/html', null, null), $page, 'downloaded'),
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/images/os-59.jpg', 200, 'image/jpeg', null, null), $image, 'downloaded'),
    ]);

    expect(file_get_contents($page))->toBe('<img src=../images/os-59.jpg>');

    (new Filesystem())->deleteDirectory($directory);
});

it('rewrites css url references to downloaded local files', function (): void {
    $directory = sys_get_temp_dir().'/wayback-css-rewrite-'.bin2hex(random_bytes(4));
    mkdir($directory.'/assets/fonts', 0777, true);

    $css = $directory.'/assets/app.css';
    $font = $directory.'/assets/fonts/site.woff2';
    file_put_contents($css, 'body{background:url("../hero.jpg")} @font-face{src:url("/assets/fonts/site.woff2")}');
    file_put_contents($directory.'/hero.jpg', 'jpg');
    file_put_contents($font, 'font');

    (new OfflineMirrorRewriter(new Filesystem()))->rewrite([
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/assets/app.css', 200, 'text/css', null, null), $css, 'downloaded'),
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/hero.jpg', 200, 'image/jpeg', null, null), $directory.'/hero.jpg', 'downloaded'),
        new DownloadResult(new CdxCapture('20200101000000', 'https://example.com/assets/fonts/site.woff2', 200, 'font/woff2', null, null), $font, 'downloaded'),
    ]);

    expect(file_get_contents($css))->toBe('body{background:url("../hero.jpg")} @font-face{src:url("fonts/site.woff2")}');

    (new Filesystem())->deleteDirectory($directory);
});
