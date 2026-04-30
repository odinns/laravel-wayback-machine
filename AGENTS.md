# Agent Notes

Build the smallest thing that solves the task.

This package reads existing Wayback Machine captures through CDX, writes manifests, and restores archived files into local storage. It does not create captures. Do not add Save Page Now as a side quest.

Frame the project as an offline viewer or restore tool for public Wayback Machine captures, usually for a domain the user owns or maintains. Avoid security-flavored wording unless the task is actually security work.

Rules:

- Keep defaults respectful: real delay, bounded exports, no pretend parallelism.
- Touch only the package surface needed for the task.
- Prefer plain DTOs and services over framework magic.
- Keep filesystem output predictable.
- Test CDX parsing, URL generation, path uniqueness, retries, and command guardrails.
- Written docs should be direct and concrete. No pitch-deck fog.
- Prefer "archived captures", "offline restore", "local static copy", and "historical browsing".
- Avoid "target", "recon", "crawl", "download all", and "mirror entire website" in docs and examples.

Before calling work done:

```bash
composer validate --strict
composer test:all
git diff --check
```
