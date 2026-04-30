# Laravel Wayback Machine

Restore existing Internet Archive Wayback Machine captures from Laravel.

This package lists public CDX captures, writes JSON manifests, and restores archived files into local storage for offline browsing. It does not create new archive.org captures. No Save Page Now. No live-site scraping dressed up as a feature.

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

You can also override output paths when the Laravel default is not where you want files to land:

```env
WAYBACK_MACHINE_MANIFESTS_PATH=/absolute/path/manifests
WAYBACK_MACHINE_CAPTURES_PATH=/absolute/path/captures
```

## Start Small

List captures first:

```bash
php artisan wayback:list example.com --limit=25
```

If you care about the first rows CDX returns, use `--selection=all`. The default `latest-per-url` mode collapses repeated captures after CDX responds.

Dry-run a download:

```bash
php artisan wayback:download https://example.com --match=exact --dry-run --limit=1
```

Write a manifest:

```bash
php artisan wayback:manifest example.com --from=202001 --to=202012 --limit=500
```

Restore a bounded archived scope:

```bash
php artisan wayback:mirror example.com --limit=100 --delay-ms=2000
```

Unbounded restores are blocked in non-interactive runs. In an interactive terminal, you must confirm them. That friction is deliberate.

`wayback:mirror` starts with the selected page captures, fetches same-scope render assets like images and stylesheets, and rewrites local references when it can. It does not follow every normal link on the page. A download table should not turn into a surprise file harvest.

Use `--ignore-errors` when you want a local page even if some optional assets fail. The command will finish faster, but the output may be incomplete.

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

Restored files use `id_` replay URLs by default. Toolbar replay URLs are still included in manifests for reference.

## Output Layout

Manifest default:

```text
storage/app/wayback-machine/manifests/{safe-scope}-{timestamp}.json
```

Restored capture default:

```text
storage/app/wayback-machine/captures/{scope}/{timestamp}/{safe-url-path}
```

Manifest entries include timestamp, original URL, replay URL, raw URL, status, MIME type, digest, length, and local path when restored.

Paths include query and port-sensitive parts so distinct URLs do not collapse into the same file.

## Respect The Archive

Defaults are conservative:

- selection: `latest-per-url`
- delay: `2000ms`
- retries: connection failures, `429`, `500`, `502`, `503`, `504`
- backoff: `1000`, `3000`, `10000`, `30000` ms

The Internet Archive is public infrastructure, not a private backup drive. Keep restores bounded. Use a clear User-Agent. Donate if this saves you work: https://archive.org/donate

## Development

Run the full local gate before pushing:

```bash
composer validate --strict
composer test:all
git diff --check
```

This package uses Testbench for command tests. In package development, Testbench writes Wayback output to this repository's `storage/` directory instead of hiding it under `vendor/orchestra`.

## Versioning

Composer versions come from Git tags. Do not add a `version` field to `composer.json`.

## Contributing

Keep changes small, tested, and boring in the right places. If you touch download or mirror behavior, test real edge cases: CDX parsing, retries, path collisions, local reference rewriting, and command guardrails.

## Security

Report security issues privately through GitHub Security Advisories when available:

```text
https://github.com/odinns/laravel-wayback-machine/security/advisories/new
```

Downloaded captures are untrusted input. Do not execute restored files.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

MIT. See [LICENSE](LICENSE).

## Non-Goals

This package does not:

- create new Wayback captures
- call Save Page Now
- ship Docker
- create migrations or Eloquent models
- classify site-specific spam or app-specific content

It reads what already exists and writes files. That is the job.
