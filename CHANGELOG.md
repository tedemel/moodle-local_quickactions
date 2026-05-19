# Changelog

All notable changes to `local_quickactions` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] — 2026-05-19

### Changed
- Promoted maturity from `MATURITY_ALPHA` to `MATURITY_STABLE`.
- Compatibility verified for Moodle 5.0–5.2 (`$plugin->supported = [500, 502]`).
- CI matrix overhauled: `MOODLE_500_STABLE/pgsql`, `MOODLE_501_STABLE/mariadb`,
  `MOODLE_502_STABLE/pgsql`, `MOODLE_502_STABLE` + PHP 8.4/mariadb.
- CI now also covers MariaDB (mariadb:10.11 service added).
- Bump version 2026050500 → 2026051903, release 0.2.0 → 1.0.0.

## [0.2.0] — 2026-05-05

### Added
- Initial release.
- Floating action button (FAB) in course edit mode, position configurable.
- Multi-select via checkboxes and/or lasso (Shift+drag).
- Four bulk actions: toggle visibility, shift dates, duplicate section, move to section.
- Preview step with before/after table for every action.
- Confirmation dialog above configurable threshold.
- Built-in 5-step user tour, auto-starts on first FAB click per session, replayable via help icon.
- Panel reopens after page reload (sessionStorage).
- Capabilities: `local/quickactions:use`, `:bulkupdate`, `:duplicatesection`.
- `null_provider` for Privacy API (no data stored).
