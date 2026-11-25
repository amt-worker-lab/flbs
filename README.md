# Repository Overview

- `php/`: legacy PHP utilities and MTurk scripts.
- `sql/`: database schema backups.
- `HITs/`: saved HIT pages used as test fixtures.
- `Chrome Extensions/`: browser extensions, including the AMT Core extension and its test runner.
- `python`: Python/Scrapy-based domain-scoped website crawling with configurable crawl limits.

## Testing

The AMT Core test runner uses jsdom with browser APIs stubbed and network access disabled. It has three checks: the worker homepage calls `amt_home_show_tasks`; a blank localhost HIT page calls `MT__RUN()` and leaves the body empty; and every file under `HITs/` is loaded into `document.body.innerHTML` and checked for `MT__RUN()` return values and exceptions. Exception output includes source locations when available.

From `Chrome Extensions/`, run:

```bash
node "AMT, Core, mturk.com, s3.amazonaws.com, mturkcontent.com/test-runner.js"
```

The runner prints a summary and exits with status 1 if a fixed test fails or a HIT fixture throws.
