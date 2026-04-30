# Contributing

Keep changes small and testable.

Before opening a pull request:

```bash
composer validate --strict
composer test:all
git diff --check
```

Do not add Save Page Now support to this package. That is a different tool with different safety rules.

If you touch download behavior, add tests for retries, delay, no-clobber behavior, and failure handling. The boring edge cases are the point.
