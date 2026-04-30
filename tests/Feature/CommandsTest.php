<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Filesystem\Filesystem;

it('lists captures as json without downloading files', function (): void {
    Http::fake([
        'web.archive.org/cdx/search/cdx*' => Http::response([
            ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'],
            ['20200101000000', 'https://example.com', '200', 'text/html', 'ABC', '10'],
        ]),
    ]);

    $this->artisan('wayback:list', ['scope' => 'example.com', '--json' => true, '--delay-ms' => 0, '--page-limit' => 1])
        ->expectsOutputToContain('https://example.com')
        ->assertSuccessful();

    Http::assertSentCount(1);
});

it('blocks unbounded mirrors in non interactive mode', function (): void {
    $this->artisan('wayback:mirror', ['scope' => 'example.com'])
        ->expectsOutputToContain('Refusing an unbounded mirror')
        ->assertFailed();
});

it('stores mirror dependencies beside the source page snapshot', function (): void {
    $output = sys_get_temp_dir().'/wayback-command-mirror-'.bin2hex(random_bytes(4));

    Http::fake(function ($request) {
        $url = (string) $request->url();
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (str_contains($url, 'cdx/search/cdx') && ($query['url'] ?? null) === 'http://example.com/') {
            return Http::response([
                ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'],
                ['20010202124700', 'http://example.com/', '200', 'text/html', 'HTML', '26'],
            ]);
        }

        if (str_contains($url, 'cdx/search/cdx') && ($query['url'] ?? null) === 'http://example.com/images/os-44.jpg') {
            return Http::response([
                ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'],
                ['20010603220753', 'http://www.example.com/images/os-44.jpg', '200', 'image/jpeg', 'JPG', '3'],
            ]);
        }

        if ($url === 'https://web.archive.org/web/20010202124700id_/http://example.com/') {
            return Http::response('<img src=images/os-44.jpg>');
        }

        if ($url === 'https://web.archive.org/web/20010603220753id_/http://example.com/images/os-44.jpg') {
            return Http::response('jpg');
        }

        return Http::response([], 404);
    });

    $this->artisan('wayback:mirror', [
        'scope' => 'http://example.com/',
        '--match' => 'exact',
        '--limit' => 1,
        '--page-limit' => 1,
        '--delay-ms' => 0,
        '--output' => $output,
    ])->assertSuccessful();

    expect(file_get_contents($output.'/http-example.com/20010202124700/example.com/index.html'))->toBe('<img src=images/os-44.jpg>')
        ->and(file_get_contents($output.'/http-example.com/20010202124700/example.com/images/os-44.jpg'))->toBe('jpg');

    (new Filesystem())->deleteDirectory($output);
});
