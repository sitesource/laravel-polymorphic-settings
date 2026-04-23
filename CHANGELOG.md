# Changelog

All notable changes to `laravel-polymorphic-settings` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/sitesource/laravel-polymorphic-settings/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/sitesource/laravel-polymorphic-settings/releases/tag/v0.1.0
