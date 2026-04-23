# Polymorphic, per-model key-value settings for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/sitesource/laravel-polymorphic-settings.svg?style=flat-square)](https://packagist.org/packages/sitesource/laravel-polymorphic-settings)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/sitesource/laravel-polymorphic-settings/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/sitesource/laravel-polymorphic-settings/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/sitesource/laravel-polymorphic-settings/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/sitesource/laravel-polymorphic-settings/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/sitesource/laravel-polymorphic-settings.svg?style=flat-square)](https://packagist.org/packages/sitesource/laravel-polymorphic-settings)

Store settings that belong to any model — a team, a user, a tenant, or nothing at all — with opt-in encryption per key and transparent caching. Think of it as `spatie/laravel-settings` but scoped to anything, not just globals.

```php
Settings::for($team)->put('theme', 'dark');
Settings::for($user)->put('api_key', $secret, encrypted: true);
Settings::global()->get('site_title');

$team->settings()->put('locale', 'en-US');
```

## Installation

```bash
composer require sitesource/laravel-polymorphic-settings
```

Run the interactive installer (detects your app's primary key type and publishes the migration):

```bash
php artisan polymorphic-settings:install
```

Or publish and migrate manually:

```bash
php artisan vendor:publish --tag="laravel-polymorphic-settings-migrations"
php artisan vendor:publish --tag="laravel-polymorphic-settings-config"
php artisan migrate
```

## Usage

_Documentation in progress. See the [test suite](tests/) for current usage patterns._

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Nathan Call](https://github.com/nathancall)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
