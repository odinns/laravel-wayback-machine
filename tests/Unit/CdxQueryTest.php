<?php

declare(strict_types=1);

use Odinns\LaravelWaybackMachine\CaptureScope;
use Odinns\LaravelWaybackMachine\CdxQuery;

it('encodes repeated cdx filters', function (): void {
    $query = new CdxQuery(
        statuses: [200, 301],
        mimeTypes: ['text/html'],
        includePatterns: ['*/news/*'],
        excludePatterns: ['*/feed/*'],
        limit: 50,
    );

    $parameters = $query->parametersFor(CaptureScope::from('example.com'), 2);

    expect($parameters['filter'])->toBe([
        'statuscode:200',
        'statuscode:301',
        'mimetype:text/html',
        'original:*/news/*',
        '!original:*/feed/*',
    ])
        ->and($parameters['page'])->toBe(2)
        ->and($parameters['matchType'])->toBe('host')
        ->and($parameters['limit'])->toBe(50);
});
