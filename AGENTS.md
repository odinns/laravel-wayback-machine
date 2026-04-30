# Agent Notes

Build the smallest thing that solves the task.

This package reads existing Wayback Machine captures through CDX, writes manifests, and downloads files. It does not create captures. Do not add Save Page Now as a side quest.

Rules:

- Keep defaults respectful: real delay, bounded mirrors, no pretend parallelism.
- Touch only the package surface needed for the task.
- Prefer plain DTOs and services over framework magic.
- Keep filesystem output predictable.
- Test CDX parsing, URL generation, path uniqueness, retries, and command guardrails.
- Written docs should be direct and concrete. No pitch-deck fog.

Before calling work done:

```bash
composer validate --strict
composer test:all
git diff --check
```
