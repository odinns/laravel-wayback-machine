<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

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
