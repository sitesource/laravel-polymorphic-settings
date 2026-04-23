<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use SiteSource\PolymorphicSettings\Facades\PolymorphicSettings as PolymorphicSettingsFacade;
use SiteSource\PolymorphicSettings\Models\Setting;
use SiteSource\PolymorphicSettings\PolymorphicSettings;
use SiteSource\PolymorphicSettings\SettingsStore;
use SiteSource\PolymorphicSettings\Tests\Support\TestTeam;

it('resolves the manager as a singleton from the container', function () {
    $a = app(PolymorphicSettings::class);
    $b = app(PolymorphicSettings::class);

    expect($a)->toBeInstanceOf(PolymorphicSettings::class);
    expect($a)->toBe($b);
});

it('resolves via the polymorphic-settings alias', function () {
    expect(app('polymorphic-settings'))
        ->toBeInstanceOf(PolymorphicSettings::class);
});

it('global() returns a fresh global SettingsStore', function () {
    $manager = new PolymorphicSettings;
    $store = $manager->global();

    expect($store)->toBeInstanceOf(SettingsStore::class);
    expect($store->isGlobal())->toBeTrue();
});

it('for() returns a scoped SettingsStore', function () {
    $team = TestTeam::create(['name' => 'Acme']);

    $store = (new PolymorphicSettings)->for($team);

    expect($store)->toBeInstanceOf(SettingsStore::class);
    expect($store->isGlobal())->toBeFalse();
});

it('for() throws on models without a primary key', function () {
    $team = new TestTeam(['name' => 'Unsaved']);

    expect(fn () => (new PolymorphicSettings)->for($team))
        ->toThrow(InvalidArgumentException::class, 'without a primary key');
});

it('for() uses getMorphClass() so morph maps are respected', function () {
    try {
        Relation::enforceMorphMap([
            'team' => TestTeam::class,
        ]);

        $team = TestTeam::create(['name' => 'Acme']);
        $store = (new PolymorphicSettings)->for($team);

        $store->put('theme', 'dark');

        // The scope_key on the stored row should use the morph map alias.
        expect(Setting::first()->scope_key)
            ->toBe('team:'.$team->id);
    } finally {
        Relation::morphMap([], false);
        Relation::requireMorphMap(false);
    }
});

it('the facade routes calls through the container', function () {
    PolymorphicSettingsFacade::global()->put('site_title', 'Acme');

    expect(PolymorphicSettingsFacade::global()->get('site_title'))->toBe('Acme');
});

it('the facade for() scopes correctly', function () {
    $team = TestTeam::create(['name' => 'Acme']);

    PolymorphicSettingsFacade::for($team)->put('theme', 'dark');

    expect(PolymorphicSettingsFacade::for($team)->get('theme'))->toBe('dark');
    expect(PolymorphicSettingsFacade::global()->get('theme'))->toBeNull();
});
