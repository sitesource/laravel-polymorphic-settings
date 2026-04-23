<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings Table
    |--------------------------------------------------------------------------
    |
    | Table name that stores every setting record. Change this if you need to
    | avoid collisions with an existing table in your application.
    |
    */

    'table' => env('POLYMORPHIC_SETTINGS_TABLE', 'polymorphic_settings'),

    /*
    |--------------------------------------------------------------------------
    | Primary Key Type
    |--------------------------------------------------------------------------
    |
    | The primary key type used by the settings table: "int" (auto-increment)
    | or "uuid". Default is "int" to match a typical Laravel installation.
    |
    | Toggle this before running migrations. The install command
    | (`php artisan polymorphic-settings:install`) will offer to detect and
    | set this automatically based on your User model.
    |
    */

    'key_type' => env('POLYMORPHIC_SETTINGS_KEY_TYPE', 'int'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Individual setting reads are cached through Laravel's cache for reduced
    | database load. Writes (put, forget) automatically invalidate the
    | corresponding cache entry.
    |
    | - enabled: set to false to bypass the cache entirely.
    | - store:   cache store name (matches keys in config/cache.php). null
    |            uses the application default store.
    | - ttl:     seconds to retain a cached value. null caches forever.
    | - prefix:  key namespace. Full cache keys look like
    |            "{prefix}:{scope_key}:{setting_key}".
    |
    */

    'cache' => [

        'enabled' => env('POLYMORPHIC_SETTINGS_CACHE_ENABLED', true),

        'store' => env('POLYMORPHIC_SETTINGS_CACHE_STORE'),

        'ttl' => env('POLYMORPHIC_SETTINGS_CACHE_TTL'),

        'prefix' => env('POLYMORPHIC_SETTINGS_CACHE_PREFIX', 'polymorphic-settings'),

    ],

];
