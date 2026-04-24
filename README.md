# Polymorphic, per-model key-value settings for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sitesource/laravel-polymorphic-settings.svg?style=flat-square)](https://packagist.org/packages/sitesource/laravel-polymorphic-settings)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/sitesource/laravel-polymorphic-settings/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/sitesource/laravel-polymorphic-settings/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/sitesource/laravel-polymorphic-settings/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/sitesource/laravel-polymorphic-settings/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/sitesource/laravel-polymorphic-settings.svg?style=flat-square)](https://packagist.org/packages/sitesource/laravel-polymorphic-settings)

Store settings that belong to **any model** — a team, a user, a tenant, or nothing at all — with opt-in encryption per key and transparent caching.

```php
use SiteSource\PolymorphicSettings\Facades\PolymorphicSettings;

PolymorphicSettings::global()->put('site_title', 'Acme');
PolymorphicSettings::for($team)->put('theme', ['mode' => 'dark']);
PolymorphicSettings::for($user)->put('api_key', $secret, encrypted: true);

PolymorphicSettings::for($team)->get('theme.mode');   // 'dark'
$team->settings()->int('max_members', 25);            // typed getters
```

## Why another settings package?

Most Laravel settings packages are **class-based and global**: you define a `GeneralSettings` class with typed properties, access `app(GeneralSettings::class)->site_name`. Great when you have a fixed schema and one set of values for your whole app.

This package is **key-based and polymorphic**: you attach settings to any Eloquent model at runtime. Per-team themes, per-user preferences, per-tenant feature flags, and plain old global settings all live in one table, all accessed through one fluent API.

| Want… | Reach for |
|---|---|
| Strongly-typed, class-based global settings | [`spatie/laravel-settings`](https://github.com/spatie/laravel-settings) |
| Runtime-scoped settings per team/user/tenant, plus encryption | **this package** |

## Installation

```bash
composer require sitesource/laravel-polymorphic-settings
```

Run the installer — it detects your User model's primary key type, publishes the config, publishes the migration, and migrates. Non-interactive runs (CI, `--no-interaction`) use detected defaults:

```bash
php artisan polymorphic-settings:install
```

Or do each step manually:

```bash
php artisan vendor:publish --tag="polymorphic-settings-config"
php artisan vendor:publish --tag="polymorphic-settings-migrations"
php artisan migrate
```

## Usage

### Global settings

```php
use SiteSource\PolymorphicSettings\Facades\PolymorphicSettings;

PolymorphicSettings::global()->put('site_title', 'Acme');
PolymorphicSettings::global()->get('site_title');   // 'Acme'
```

### Scoped to any Eloquent model

```php
PolymorphicSettings::for($team)->put('theme', 'dark');
PolymorphicSettings::for($team)->get('theme');      // 'dark'

// Same key, different scopes — fully isolated
PolymorphicSettings::for($userA)->put('locale', 'en-US');
PolymorphicSettings::for($userB)->put('locale', 'fr-FR');
```

### Via the `HasSettings` trait

Clean up calling sites by adding the trait to your models:

```php
use SiteSource\PolymorphicSettings\Concerns\HasSettings;

class Team extends Model
{
    use HasSettings;
}

$team->settings()->put('theme', 'dark');
$team->settings()->get('theme');          // 'dark'

// Eager load to avoid N+1
Team::with('scopedSettings')->get();
```

### Opt-in cascade delete

Settings survive when their parent model is deleted by default. If you'd rather have them cleaned up:

```php
class Team extends Model
{
    use HasSettings;

    public bool $cascadeDeleteSettings = true;
}

$team->delete();    // settings for this team are purged
```

`SoftDeletes` is respected — soft deletes **don't** cascade (so `restore()` works), only `forceDelete()` triggers the cleanup.

### Encryption

Per-key, opt-in via a named argument:

```php
$user->settings()->put('api_key', 'sk-live-abc', encrypted: true);
$user->settings()->get('api_key');        // 'sk-live-abc' (transparently decrypted)
```

Encryption is transparent — `all()`, `getMany()`, typed getters and dot-notation reads all see the plaintext. The raw DB value never contains the plaintext.

You can promote a plain setting to encrypted (and vice-versa) just by re-putting with a different `encrypted:` argument.

### Bulk operations

```php
$team->settings()->putMany([
    'theme' => 'dark',
    'locale' => 'en-US',
    'timezone' => 'UTC',
]);

$team->settings()->getMany(['theme', 'locale', 'missing']);
// ['theme' => 'dark', 'locale' => 'en-US', 'missing' => null]

$team->settings()->all();               // every setting in this scope
$team->settings()->forgetAll();         // nuke every setting in this scope
```

### Dot-notation reads

```php
$team->settings()->put('theme', [
    'palette' => ['primary' => ['hex' => '#abc123']],
]);

$team->settings()->get('theme.palette.primary.hex');   // '#abc123'
```

Dot-notation is a **read-time convenience** for walking into an array value. Keys are always stored literally — `put('commerce.foo', true)` and `get('commerce.foo')` round-trip against a row whose stored key is the string `commerce.foo`. `get()` only falls back to nested-path traversal when no literal match exists, so you can safely use dotted keys as namespaced flat identifiers.

`has()`, `forget()`, and `pull()` are always literal. `has('theme.mode')` asks "is there a row with that exact key", not "can I reach that nested path through the `theme` row".

### Typed getters

No implicit coercion — if the stored value is the wrong type, the default is returned:

```php
$team->settings()->string('theme', 'default');
$team->settings()->int('max_members', 25);
$team->settings()->float('rate', 1.0);
$team->settings()->bool('feature_x', false);
$team->settings()->array('tags', []);
```

### Miscellaneous

```php
$team->settings()->has('theme');           // true
$team->settings()->forget('theme');         // bool — whether it existed
$team->settings()->pull('theme');           // get + forget atomically
$team->settings()->updateValues('theme', [  // merge into an existing array
    'accent' => '#f00',
]);
```

## Configuration

`config/polymorphic-settings.php` (all values can also be set via env vars):

```php
return [
    'table'    => env('POLYMORPHIC_SETTINGS_TABLE', 'polymorphic_settings'),
    'key_type' => env('POLYMORPHIC_SETTINGS_KEY_TYPE', 'int'),   // 'int' | 'uuid'

    'cache' => [
        'enabled' => env('POLYMORPHIC_SETTINGS_CACHE_ENABLED', true),
        'store'   => env('POLYMORPHIC_SETTINGS_CACHE_STORE'),    // null = default
        'ttl'     => env('POLYMORPHIC_SETTINGS_CACHE_TTL'),      // null = forever
        'prefix'  => env('POLYMORPHIC_SETTINGS_CACHE_PREFIX', 'polymorphic-settings'),
    ],
];
```

### Caching

Reads are cached through Laravel's cache. `put` and `forget` invalidate automatically. Cache keys are scoped (`{prefix}:{scope_key}:{setting_key}`) so two different models can never collide.

Missing keys are intentionally not cached to sidestep cross-driver null-caching quirks. If you hammer `get('nonexistent')` in a hot path, either supply a default or populate the key.

### UUID primary keys

The installer asks which PK type to use and auto-detects from your User model. If your app uses UUIDs:

```env
POLYMORPHIC_SETTINGS_KEY_TYPE=uuid
```

The `configurable_id` column is always stored as a string, so int-keyed and UUID-keyed parent models can coexist in the same settings table regardless of this setting.

### Using `Settings` as the facade alias

`PolymorphicSettings` is a mouthful. If you'd prefer `Settings::for($team)->get(...)`:

```php
// config/app.php
'aliases' => [
    'Settings' => SiteSource\PolymorphicSettings\Facades\PolymorphicSettings::class,
],
```

The default facade alias stays `PolymorphicSettings` to avoid collisions with any existing `Settings` class in your app.

## Testing

```bash
composer test
```

The suite runs against SQLite, MySQL, and Postgres in CI (120 tests, ~194 assertions as of v0.1.0).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nathan Call](https://github.com/nathancall)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
