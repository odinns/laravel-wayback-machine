<?php

declare(strict_types=1);

use Odinns\LaravelWaybackMachine\CaptureScope;
use Odinns\LaravelWaybackMachine\CdxCapture;
use Odinns\LaravelWaybackMachine\OutputPathBuilder;

it('does not collide paths for query strings and ports', function (): void {
    $scope = CaptureScope::from('example.com');
    $builder = new OutputPathBuilder();

    $first = $builder->capturePath($scope, new CdxCapture('20200101000000', 'https://example.com:8443/page?a=1', 200, null, null, null), '/tmp/out');
    $second = $builder->capturePath($scope, new CdxCapture('20200101000000', 'https://example.com/page?a=2', 200, null, null, null), '/tmp/out');

    expect($first)->not->toBe($second)
        ->and($first)->toContain('port-8443')
        ->and($second)->toContain('query-');
});

it('strips traversal segments from output paths', function (): void {
    $scope = CaptureScope::from('example.com');
    $builder = new OutputPathBuilder();

    $path = $builder->capturePath(
        $scope,
        new CdxCapture('20200101000000', 'https://example.com/../../etc/passwd', 200, null, null, null),
        '/tmp/out',
    );

    expect($path)->toBe('/tmp/out/example.com/20200101000000/example.com/etc/passwd')
        ->and($path)->not->toContain('../');
});

it('places mirror dependencies under the source capture timestamp', function (): void {
    $scope = CaptureScope::from('https://example.com/', 'exact');
    $builder = new OutputPathBuilder();

    $path = $builder->dependencyPath(
        $scope,
        new CdxCapture('20010202124700', 'https://example.com/', 200, 'text/html', null, null),
        new CdxCapture('20010603220753', 'https://example.com/images/os-44.jpg', 200, 'image/jpeg', null, null),
        '/tmp/out',
    );

    expect($path)->toBe('/tmp/out/https-example.com/20010202124700/example.com/images/os-44.jpg');
});
