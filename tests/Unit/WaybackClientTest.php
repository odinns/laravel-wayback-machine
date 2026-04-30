<?php

declare(strict_types=1);

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Odinns\LaravelWaybackMachine\CaptureScope;
use Odinns\LaravelWaybackMachine\CdxParser;
use Odinns\LaravelWaybackMachine\CdxQuery;
use Odinns\LaravelWaybackMachine\ReplayUrlBuilder;
use Odinns\LaravelWaybackMachine\Support\GlobalRequestDelay;
use Odinns\LaravelWaybackMachine\WaybackClient;
use Odinns\LaravelWaybackMachine\WaybackOptions;

it('paginates cdx queries with resume keys', function (): void {
    Http::fakeSequence()
        ->push([
            ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'],
            ['20200101000000', 'https://example.com/one', '200', 'text/html', 'AAA', '10'],
            ['resume-1'],
        ])
        ->push([
            ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'],
            ['20200102000000', 'https://example.com/two', '200', 'text/html', 'BBB', '20'],
        ]);

    config()->set('wayback-machine.cdx_endpoint', 'https://web.archive.org/cdx/search/cdx');

    $client = new WaybackClient(app(Factory::class), new CdxParser(), new ReplayUrlBuilder(), new GlobalRequestDelay(0));
    $captures = $client->captures(
        CaptureScope::from('example.com'),
        new CdxQuery(),
        new WaybackOptions(delayMs: 0, userAgent: 'test-agent', selection: 'all'),
    );

    expect($captures)->toHaveCount(2)
        ->and($captures[1]->originalUrl)->toBe('https://example.com/two');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://web.archive.org/cdx/search/cdx?url=example.com&matchType=host&output=json&fl=timestamp%2Coriginal%2Cstatuscode%2Cmimetype%2Cdigest%2Clength&showResumeKey=true&page=0');
    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'resumeKey=resume-1'));
});

it('filters captures client side when cdx returns extra rows', function (): void {
    Http::fake([
        'web.archive.org/cdx/search/cdx*' => Http::response([
            ['timestamp', 'original', 'statuscode', 'mimetype', 'digest', 'length'],
            ['20200101000000', 'https://example.com/', '200', 'text/html', 'AAA', '10'],
            ['20200102000000', 'https://example.com/images/logo.png', '200', 'image/png', 'BBB', '20'],
            ['20200103000000', 'https://example.com/images/skip.png', '404', 'image/png', 'CCC', '30'],
            ['20200104000000', 'https://example.com/images/feed.png', '200', 'image/png', 'DDD', '40'],
        ]),
    ]);

    config()->set('wayback-machine.cdx_endpoint', 'https://web.archive.org/cdx/search/cdx');

    $client = new WaybackClient(app(Factory::class), new CdxParser(), new ReplayUrlBuilder(), new GlobalRequestDelay(0));
    $captures = $client->captures(
        CaptureScope::from('example.com'),
        new CdxQuery(
            statuses: [200],
            mimeTypes: ['image/png'],
            includePatterns: ['.*images.*'],
            excludePatterns: ['*feed*'],
        ),
        new WaybackOptions(delayMs: 0, userAgent: 'test-agent', selection: 'all'),
    );

    expect($captures)->toHaveCount(1)
        ->and($captures[0]->originalUrl)->toBe('https://example.com/images/logo.png');
});
