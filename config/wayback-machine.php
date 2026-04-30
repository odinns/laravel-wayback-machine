<?php

declare(strict_types=1);

return [
    'cdx_endpoint' => env('WAYBACK_MACHINE_CDX_ENDPOINT', 'https://web.archive.org/cdx/search/cdx'),
    'replay_root' => env('WAYBACK_MACHINE_REPLAY_ROOT', 'https://web.archive.org'),
    'timeout' => (int) env('WAYBACK_MACHINE_TIMEOUT', 60),
    'delay_ms' => (int) env('WAYBACK_MACHINE_DELAY_MS', 2000),
    'user_agent' => env('WAYBACK_MACHINE_USER_AGENT', 'odinns/laravel-wayback-machine (+https://github.com/odinns/laravel-wayback-machine)'),
    'retry_statuses' => [429, 500, 502, 503, 504],
    'retry_backoff_ms' => [1000, 3000, 10000, 30000],
    'paths' => [
        'manifests' => storage_path('app/wayback-machine/manifests'),
        'captures' => storage_path('app/wayback-machine/captures'),
    ],
];
