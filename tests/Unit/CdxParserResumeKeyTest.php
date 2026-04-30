<?php

declare(strict_types=1);

use Odinns\LaravelWaybackMachine\CdxParser;

it('handles resume key only cdx responses as empty pages', function (): void {
    $page = (new CdxParser())->parsePage(json_encode([
        ['resumeKey'],
        ['next-page'],
    ], JSON_THROW_ON_ERROR));

    expect($page->captures)->toBe([])
        ->and($page->resumeKey)->toBe('next-page');
});

it('ignores the empty separator row before a resume key', function (): void {
    $page = (new CdxParser())->parsePage(json_encode([
        ['timestamp', 'original'],
        ['20010202124700', 'http://odinns.dk:80/'],
        [],
        ['next-page'],
    ], JSON_THROW_ON_ERROR));

    expect($page->captures)->toHaveCount(1)
        ->and($page->captures[0]->timestamp)->toBe('20010202124700')
        ->and($page->resumeKey)->toBe('next-page');
});
