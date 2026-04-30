<?php

declare(strict_types=1);

use Odinns\LaravelWaybackMachine\CdxCapture;
use Odinns\LaravelWaybackMachine\ReplayUrlBuilder;

it('builds raw id urls by default and toolbar urls on request', function (): void {
    $capture = new CdxCapture('20200101000000', 'https://example.com/page', 200, 'text/html', 'ABC', 123);
    $builder = new ReplayUrlBuilder('https://web.archive.test');

    expect($builder->raw($capture))->toBe('https://web.archive.test/web/20200101000000id_/https://example.com/page')
        ->and($builder->toolbar($capture))->toBe('https://web.archive.test/web/20200101000000/https://example.com/page');
});
