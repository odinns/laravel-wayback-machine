# Laravel Wayback Machine

Read existing Internet Archive Wayback Machine captures from Laravel.

This package lists CDX captures, writes JSON manifests, and downloads raw archived files. It does not create new archive.org captures. No Save Page Now. No crawler pretending the Internet Archive is your build server.

## Install

```bash
composer require odinns/laravel-wayback-machine
```

Publish the config when you want to change defaults:

```bash
php artisan vendor:publish --tag=wayback-machine-config
```

Set a real User-Agent for your app:

```env
WAYBACK_MACHINE_USER_AGENT="example-site-restore/1.0 (you@example.com)"
```

## Start Small

List captures first:

```bash
php artisan wayback:list example.com --limit=25
```

Dry-run a download:

```bash
php artisan wayback:download https://example.com --match=exact --dry-run --limit=1
```

Write a manifest:

```bash
php artisan wayback:manifest example.com --from=202001 --to=202012 --limit=500
```

Mirror a bounded scope:

```bash
php artisan wayback:mirror example.com --limit=100 --delay-ms=2000
```

Unbounded mirrors are blocked in non-interactive runs. In an interactive terminal, you must confirm them. That friction is deliberate.

## Commands

```bash
php artisan wayback:list {scope}
php artisan wayback:manifest {scope}
php artisan wayback:download {url-or-scope}
php artisan wayback:mirror {scope}
```

Shared options:

```text
--match=host|prefix|exact
--from= --to=
--status= --mime=
--include= --exclude=
--selection=latest-per-url|unique-content|all
--collapse=<cdx-collapse>
--limit= --page-limit=
--delay-ms=2000
--timeout=60
--user-agent="..."
--replay-root=https://web.archive.org
--follow-redirects
--ignore-errors
--dry-run
--json
--progress
--force
--output=/path
```

`--selection` is the friendly mode. `--collapse` is the raw CDX escape hatch.

`latest-per-url` keeps the newest capture for each normalized URL. `unique-content` deduplicates by digest. `all` keeps every matching capture.

## API

```php
use Odinns\LaravelWaybackMachine\CaptureManifest;
use Odinns\LaravelWaybackMachine\CaptureScope;
use Odinns\LaravelWaybackMachine\CdxQuery;
use Odinns\LaravelWaybackMachine\ManifestWriter;
use Odinns\LaravelWaybackMachine\WaybackClient;

$scope = CaptureScope::from('example.com', 'host');

$query = new CdxQuery(
    from: '202001',
    to: '202012',
    statuses: [200],
    mimeTypes: ['text/html'],
    limit: 100,
);

$captures = app(WaybackClient::class)->captures($scope, $query);

app(ManifestWriter::class)->write(
    new CaptureManifest($scope, $captures),
    storage_path('app/wayback-machine/manifests/example.json'),
);
```

Raw downloads use `id_` replay URLs by default. Toolbar replay URLs are still included in manifests for reference.

## Output Layout

Manifest default:

```text
storage/app/wayback-machine/manifests/{safe-scope}-{timestamp}.json
```

Capture default:

```text
storage/app/wayback-machine/captures/{scope}/{timestamp}/{safe-url-path}
```

Manifest entries include timestamp, original URL, replay URL, raw URL, status, MIME type, digest, length, and local path when downloaded.

Paths include query and port-sensitive parts so distinct URLs do not collapse into the same file.

## Respect The Archive

Defaults are conservative:

- selection: `latest-per-url`
- delay: `2000ms`
- retries: connection failures, `429`, `500`, `502`, `503`, `504`
- backoff: `1000`, `3000`, `10000`, `30000` ms

The Internet Archive is public infrastructure, not an infinite hose. Keep mirrors bounded. Use a clear User-Agent. Donate if this saves you work: https://archive.org/donate


## Non-Goals

This package does not:

- create new Wayback captures
- call Save Page Now
- ship Docker
- create migrations or Eloquent models
- classify site-specific spam or app-specific content

It reads what already exists and writes files. That is the job.
