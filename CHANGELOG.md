# Changelog

All notable changes to `laravel-polymorphic-settings` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.3] - 2026-04-24

### Fixed

- **Cache poisoning when migrating dotted-literal storage to nested storage** (reported by Kimber v2). Reading a dotted key (e.g. `get('commerce.foo')`) used to cache the literal-row lookup under `cacheKey('commerce.foo')`. A subsequent `put('commerce', ['foo' => false])` only invalidated `cacheKey('commerce')`, leaving the dotted-child entry stale until TTL. The fix: skip the cache layer for the literal lookup of any dotted key. The root-traversal path stays cached, so `put('root', ...)` still invalidates correctly. Net effect: literal dotted-key reads now cost one DB query each (instead of one then-cached); nested-array reads are unchanged.
- **`forget()` no longer no-ops when the row is already gone.** Previously the `cacheForget()` call was guarded by `if ($deleted)`, so calling `forget('foo')` after the row had been removed externally (sibling process, manual SQL, a prior pass that nuked the table) failed to invalidate the cached value. Now `forget()` always busts the cache; the boolean return still reflects whether a DB row was deleted.

### Workaround for prior versions

If you can't upgrade yet, set `POLYMORPHIC_SETTINGS_CACHE_ENABLED=false` in your environment to bypass caching entirely.

## [0.1.2] - 2026-04-24

### Fixed

- **Dot-notation asymmetry in `get()`** ([#kimber-v2 report]). Keys were treated
  as literal by `put()`, `has()`, `forget()`, and `pull()` but dot-split on
  `get()`. Writing a key that contained a dot (e.g. `put('commerce.foo', true)`)
  succeeded silently, and the matching `get('commerce.foo')` always returned
  the default — `get()` was looking for `foo` inside a `commerce` array that
  never existed. `get()` now tries a literal match first and only falls back
  to nested-path traversal when the literal key is absent **and** the key
  contains a dot. All other methods remain literal-only. This is a strict
  superset of previous behaviour — existing nested reads (e.g. `get('theme.mode')`
  against a stored `theme = ['mode' => 'dark']`) still work the same way.

## [0.1.1] - 2026-04-22

### Changed

- Packagist keywords broadened for discoverability: added `settings`, `laravel-settings`, `polymorphic`, `model-settings`, `eloquent`, `configuration`, `multi-tenant`, `encryption`, `preferences`. Dropped `SiteSource` and the redundant `laravel-polymorphic-settings` slug. Metadata-only change — no code differences from 0.1.0.

## [0.1.0] - 2026-04-22

Initial public release.

### Added

- `PolymorphicSettings` manager with `global()` and `for($model)` scope builders.
- `SettingsStore` fluent API: `get`, `put`, `putMany`, `getMany`, `has`, `forget`, `forgetAll`, `pull`, `all`, `updateValues`, plus `string`, `int`, `float`, `bool`, `array` typed getters.
- Dot-notation reads for nested JSON values: `get('theme.palette.primary.hex')`.
- Per-key encryption via `put($key, $value, encrypted: true)`. Encryption is transparent across all read paths.
- Cache-through reads with per-key invalidation on `put`/`forget`. Cache keys are scoped so different models never collide.
- `HasSettings` trait exposing `$model->settings()` and a `scopedSettings` morphMany relation for eager loading.
- Opt-in cascade delete via `public bool $cascadeDeleteSettings = true;` on the host model. Soft-delete aware — only `forceDelete()` triggers cleanup.
- Configurable primary key type (`int` default, `uuid` opt-in) via env var, config file, or the install command's interactive detector.
- `polymorphic-settings:install` Artisan command with Laravel Prompts TUI and `--no-interaction` support for CI.
- Tested against PHP 8.3 / 8.4 / 8.5 and Laravel 12 / 13 on SQLite, MySQL, and Postgres.

[Unreleased]: https://github.com/sitesource/laravel-polymorphic-settings/compare/v0.1.3...HEAD
[0.1.3]: https://github.com/sitesource/laravel-polymorphic-settings/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/sitesource/laravel-polymorphic-settings/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/sitesource/laravel-polymorphic-settings/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/sitesource/laravel-polymorphic-settings/releases/tag/v0.1.0
