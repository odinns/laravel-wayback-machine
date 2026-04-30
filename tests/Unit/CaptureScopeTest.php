<?php

declare(strict_types=1);

use Odinns\LaravelWaybackMachine\CaptureScope;

it('normalizes host prefix and exact scopes', function (): void {
    expect(CaptureScope::from('https://example.com/path', 'host')->cdxUrl())->toBe('example.com')
        ->and(CaptureScope::from('https://example.com/path', 'host')->cdxMatchType())->toBe('host')
        ->and(CaptureScope::from('example.com/docs', 'prefix')->cdxUrl())->toBe('https://example.com/docs')
        ->and(CaptureScope::from('example.com/docs', 'exact')->cdxUrl())->toBe('https://example.com/docs');
});
