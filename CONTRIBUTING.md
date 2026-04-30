# Contributing

Keep changes small and testable.

Before opening a pull request:

```bash
composer validate --strict
composer test:all
git diff --check
```

Do not add Save Page Now support to this package. That is a different tool with different safety rules.

If you touch download or mirror behavior, test the boring edge cases: retries, delay, no-clobber behavior, path collisions, failed captures, and local reference rewriting.
