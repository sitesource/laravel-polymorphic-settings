# Changelog

All notable changes to `laravel-polymorphic-settings` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/sitesource/laravel-polymorphic-settings/compare/v0.1.0 — Initial public release...HEAD)

## [0.1.0](https://github.com/sitesource/laravel-polymorphic-settings/releases/tag/v0.1.0) - 2026-04-22

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

## [v0.1.0 — Initial public release](https://github.com/sitesource/laravel-polymorphic-settings/compare/v0.1.0...v0.1.0 — Initial public release) - 2026-04-23

### 🎉 First public release

**Polymorphic, per-model key-value settings for Laravel with opt-in encryption and transparent caching.**

```bash
composer require sitesource/laravel-polymorphic-settings
php artisan polymorphic-settings:install

```
```php
use SiteSource\PolymorphicSettings\Facades\PolymorphicSettings;

PolymorphicSettings::global()->put('site_title', 'Acme');
PolymorphicSettings::for(\$team)->put('theme', ['mode' => 'dark']);
PolymorphicSettings::for(\$user)->put('api_key', \$secret, encrypted: true);

\$team->settings()->get('theme.mode');   // dot-notation, cache-hit

```
#### What's in 0.1.0

- **Polymorphic scoping** — attach settings to any Eloquent model, or store them globally; all in one table
- **Per-key encryption** (`put(\$key, \$value, encrypted: true)`) with transparent decryption across all read paths
- **Cache-through reads** with per-key invalidation, configurable store/TTL/prefix
- **Fluent API** on `SettingsStore`: `get`, `put`, `putMany`, `getMany`, `has`, `forget`, `forgetAll`, `pull`, `all`, `updateValues`, plus typed getters (`string`, `int`, `float`, `bool`, `array`)
- **Dot-notation reads** for nested JSON values
- **`HasSettings` trait** with opt-in cascade delete (soft-delete-aware)
- **`polymorphic-settings:install`** Artisan command with interactive PK-type detection and full `--no-interaction` support

#### Supported versions

- PHP 8.3 / 8.4 / 8.5
- Laravel 12 / 13
- MySQL, PostgreSQL, SQLite (tested in CI)

#### Test coverage

119 Pest tests, 193 assertions, PHPStan level 5, Pint clean, security review passed.

#### Full changelog

See [CHANGELOG.md](https://github.com/sitesource/laravel-polymorphic-settings/blob/main/CHANGELOG.md).
