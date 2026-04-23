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

];
