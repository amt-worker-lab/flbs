# Repository Guidelines

## Project Structure & Module Organization

This repository is a collection of browser extensions, legacy PHP utilities, SQL schema backups, and archived Mechanical Turk task examples. `Chrome Extensions/` contains multiple extensions, shared JavaScript utilities, the top-level `package.json`, lockfile, and ESLint configuration. Extension folders commonly contain their own `manifest.json`; `Tools for Amazon's Mechanical Turk, GH/` includes separate `Chrome/` and `Firefox/` versions, and the Firefox project has its own package manifest and README. `php/` contains standalone PHP scripts for MTurk workflows, data handling, and utilities. `sql/` contains four database schema backup files for income records, AMT records, batches, and alerts. `HITs/` holds saved task pages and related historical examples, including HTML and PDF files. Preserve existing folder and file names, including spaces and punctuation, when working in this archive.

## Build, Test, and Development Commands

Run commands from `Chrome Extensions/` for shared JavaScript tools, or from the target project folder when it has its own package manifest (for example, `Chrome Extensions/Tools for Amazon's Mechanical Turk, GH/Firefox/`):

- `npm install` installs the JavaScript parser, walker, and lint dependencies.
- `npx eslint .` checks JavaScript against the repository's ESLint 9 recommended rules and browser globals.
- `node "test-esprima.js"` and `node "test-esprima-ast.js"` run the small parser experiments in the directory.

There is no repository-wide build or unified test script. Inspect the target extension's manifest and README before packaging or loading it in a browser. The Firefox project may define additional scripts in its own `package.json`. PHP and SQL have no root-level build or test commands; use the intended PHP runtime/database environment and review any script or schema carefully before running it against data.

## Coding Style & Naming Conventions

Use JavaScript compatible with the browsers targeted by the extension. Follow the existing ESLint configuration: browser globals are available and recommended JavaScript rules apply. Use consistent two-space indentation in new JavaScript, semicolons, and descriptive camelCase identifiers. Keep changes scoped to the relevant extension directory; do not reformat archived HTML or generated bundles as part of unrelated work.

## Testing Guidelines

The repository has no centralized test framework or coverage threshold. Run ESLint for JavaScript edits, and execute relevant standalone Node scripts when touching parser utilities. Follow any project-specific instructions in its README or package scripts. For extension behavior changes, load the target extension using its manifest in the appropriate browser and verify the affected workflow manually. For PHP changes, run `php -l path/to/file.php` where PHP is available; validate SQL changes against a disposable or development database. Add focused tests near the code they cover when a project gains automated tests.

## Commit & Pull Request Guidelines

Recent history uses short, imperative summaries such as `Refactor, code clean-up`, `Update`, and `Test docs`; prefer a concise verb-led subject that names the change. Pull requests should identify the affected extension or archive area, explain user-visible behavior changes, list validation performed, and include screenshots for UI changes. Link related issues when available.

## Security & Configuration

Do not commit credentials, private account data, or real service tokens. Treat saved HIT pages and bundled extension scripts as potentially sensitive historical data; avoid copying personal or task-specific information into new examples. Keep `node_modules/` untracked as configured in the extension directory's `.gitignore`.
