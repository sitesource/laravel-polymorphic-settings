<?php

use SiteSource\PolymorphicSettings\Models\Setting;

it('can create a global setting', function () {
    $setting = Setting::create([
        'key' => 'site_title',
        'value' => 'Acme Co',
    ]);

    expect($setting->fresh())
        ->key->toBe('site_title')
        ->value->toBe('Acme Co')
        ->encrypted->toBeFalse()
        ->configurable_id->toBeNull()
        ->configurable_type->toBeNull();
});

it('stores array values as JSON', function () {
    $setting = Setting::create([
        'key' => 'theme',
        'value' => ['mode' => 'dark', 'accent' => '#ff0000'],
    ]);

    expect($setting->fresh()->value)->toBe([
        'mode' => 'dark',
        'accent' => '#ff0000',
    ]);
});

it('can scope a setting to a polymorphic parent', function () {
    $setting = Setting::create([
        'key' => 'locale',
        'value' => 'en-US',
        'configurable_id' => '42',
        'configurable_type' => 'App\Models\Team',
    ]);

    expect($setting->fresh())
        ->configurable_id->toBe('42')
        ->configurable_type->toBe('App\Models\Team');
});

it('allows the same key in different scopes', function () {
    Setting::create(['key' => 'theme', 'value' => 'light']);
    Setting::create([
        'key' => 'theme',
        'value' => 'dark',
        'configurable_id' => '1',
        'configurable_type' => 'App\Models\Team',
    ]);
    Setting::create([
        'key' => 'theme',
        'value' => 'sepia',
        'configurable_id' => '2',
        'configurable_type' => 'App\Models\Team',
    ]);

    expect(Setting::count())->toBe(3);
});

it('rejects duplicate keys within the same scope', function () {
    Setting::create(['key' => 'foo', 'value' => 'bar']);

    expect(fn () => Setting::create(['key' => 'foo', 'value' => 'baz']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('rejects duplicate keys within the same polymorphic scope', function () {
    Setting::create([
        'key' => 'theme',
        'value' => 'dark',
        'configurable_id' => '1',
        'configurable_type' => 'App\Models\Team',
    ]);

    expect(fn () => Setting::create([
        'key' => 'theme',
        'value' => 'light',
        'configurable_id' => '1',
        'configurable_type' => 'App\Models\Team',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('accepts nullable value column', function () {
    $setting = Setting::create([
        'key' => 'cleared_value',
        'value' => null,
    ]);

    expect($setting->fresh()->value)->toBeNull();
});

it('defaults encrypted to false', function () {
    $setting = Setting::create(['key' => 'plain', 'value' => 'visible']);

    expect($setting->fresh()->encrypted)->toBeFalse();
});

it('casts encrypted to boolean', function () {
    $setting = Setting::create([
        'key' => 'secret',
        'value' => 'hidden',
        'encrypted' => true,
    ]);

    expect($setting->fresh()->encrypted)->toBeTrue();
});

it('uses int keys by default', function () {
    expect(config('polymorphic-settings.key_type'))->toBe('int');

    $setting = Setting::create(['key' => 'foo', 'value' => 'bar']);

    expect($setting->fresh()->id)
        ->toBeInt()
        ->toBeGreaterThan(0);
});
