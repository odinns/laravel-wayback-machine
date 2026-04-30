<?php

declare(strict_types=1);

use Odinns\LaravelWaybackMachine\CdxParser;

it('maps rows by the returned header', function (): void {
    $captures = (new CdxParser())->parse(json_encode([
        ['original', 'timestamp', 'digest'],
        ['https://example.com', '20200101000000', 'ABC'],
    ], JSON_THROW_ON_ERROR));

    expect($captures)->toHaveCount(1)
        ->and($captures[0]->timestamp)->toBe('20200101000000')
        ->and($captures[0]->originalUrl)->toBe('https://example.com')
        ->and($captures[0]->digest)->toBe('ABC')
        ->and($captures[0]->status)->toBeNull();
});
