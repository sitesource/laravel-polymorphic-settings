<?php

use SiteSource\PolymorphicSettings\Models\Setting;
use SiteSource\PolymorphicSettings\SettingsStore;
use SiteSource\PolymorphicSettings\Tests\Support\CascadingTeam;
use SiteSource\PolymorphicSettings\Tests\Support\SoftCascadingTeam;
use SiteSource\PolymorphicSettings\Tests\Support\TestTeam;

describe('settings() method', function () {
    it('returns a SettingsStore scoped to the model', function () {
        $team = TestTeam::create(['name' => 'Acme']);

        expect($team->settings())
            ->toBeInstanceOf(SettingsStore::class)
            ->and($team->settings()->isGlobal())
            ->toBeFalse();
    });

    it('routes reads and writes through the model scope', function () {
        $team = TestTeam::create(['name' => 'Acme']);

        $team->settings()->put('theme', 'dark');

        expect($team->settings()->get('theme'))->toBe('dark');
    });

    it('isolates settings between different model instances', function () {
        $a = TestTeam::create(['name' => 'A']);
        $b = TestTeam::create(['name' => 'B']);

        $a->settings()->put('theme', 'dark');
        $b->settings()->put('theme', 'light');

        expect($a->settings()->get('theme'))->toBe('dark');
        expect($b->settings()->get('theme'))->toBe('light');
    });
});

describe('scopedSettings() relation', function () {
    it('exposes a morphMany relation', function () {
        $team = TestTeam::create(['name' => 'Acme']);
        $team->settings()->putMany([
            'theme' => 'dark',
            'locale' => 'en-US',
        ]);

        $relation = $team->scopedSettings;

        expect($relation)->toHaveCount(2);
        expect($relation->pluck('key')->sort()->values()->all())
            ->toBe(['locale', 'theme']);
    });

    it('allows eager loading to avoid N+1', function () {
        $teams = collect(['A', 'B', 'C'])->map(function ($name) {
            $team = TestTeam::create(['name' => $name]);
            $team->settings()->put('theme', strtolower($name));

            return $team;
        });

        $loaded = TestTeam::with('scopedSettings')
            ->whereIn('id', $teams->pluck('id'))
            ->get();

        foreach ($loaded as $team) {
            expect($team->relationLoaded('scopedSettings'))->toBeTrue();
            expect($team->scopedSettings)->toHaveCount(1);
        }
    });
});

describe('cascade delete — default (off)', function () {
    it('leaves settings in place when the model is deleted', function () {
        $team = TestTeam::create(['name' => 'Acme']);
        $team->settings()->putMany([
            'theme' => 'dark',
            'locale' => 'en-US',
        ]);

        $team->delete();

        expect(Setting::count())->toBe(2);
    });
});

describe('cascade delete — enabled', function () {
    it('purges settings when the model is deleted', function () {
        $team = CascadingTeam::create(['name' => 'Acme']);
        $team->settings()->putMany([
            'theme' => 'dark',
            'locale' => 'en-US',
        ]);

        $team->delete();

        expect(Setting::count())->toBe(0);
    });

    it('only purges settings for the deleted model, not siblings', function () {
        $a = CascadingTeam::create(['name' => 'A']);
        $b = CascadingTeam::create(['name' => 'B']);
        $a->settings()->put('theme', 'dark');
        $b->settings()->put('theme', 'light');

        $a->delete();

        expect(Setting::count())->toBe(1);
        expect($b->settings()->get('theme'))->toBe('light');
    });

    it('invalidates cached reads for the deleted model', function () {
        $team = CascadingTeam::create(['name' => 'Acme']);
        $team->settings()->put('theme', 'dark');
        $team->settings()->get('theme'); // prime cache

        $id = $team->id;
        $morphClass = $team->getMorphClass();

        $team->delete();

        // Recreate a store pointing at the same scope_key — cache should
        // not be leaking the old value.
        $ghostStore = new SettingsStore($morphClass, (string) $id);
        expect($ghostStore->get('theme'))->toBeNull();
    });
});

describe('cascade delete — soft delete aware', function () {
    it('does NOT purge settings on soft delete', function () {
        $team = SoftCascadingTeam::create(['name' => 'Acme']);
        $team->settings()->put('theme', 'dark');

        $team->delete(); // soft delete

        expect($team->trashed())->toBeTrue();
        expect(Setting::count())->toBe(1);
        expect($team->settings()->get('theme'))->toBe('dark');
    });

    it('restores work correctly because settings were never purged', function () {
        $team = SoftCascadingTeam::create(['name' => 'Acme']);
        $team->settings()->put('theme', 'dark');

        $team->delete();
        $team->restore();

        expect($team->settings()->get('theme'))->toBe('dark');
    });

    it('purges settings on force delete', function () {
        $team = SoftCascadingTeam::create(['name' => 'Acme']);
        $team->settings()->putMany([
            'theme' => 'dark',
            'locale' => 'en-US',
        ]);

        $team->forceDelete();

        expect(Setting::count())->toBe(0);
    });
});

describe('SettingsStore::forgetAll()', function () {
    it('deletes all settings in the current scope', function () {
        $team = TestTeam::create(['name' => 'Acme']);
        $team->settings()->putMany([
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]);

        expect($team->settings()->forgetAll())->toBe(3);
        expect($team->settings()->all())->toBe([]);
    });

    it('does not touch settings in other scopes', function () {
        $a = TestTeam::create(['name' => 'A']);
        $b = TestTeam::create(['name' => 'B']);
        $a->settings()->put('theme', 'dark');
        $b->settings()->put('theme', 'light');

        $a->settings()->forgetAll();

        expect($a->settings()->all())->toBe([]);
        expect($b->settings()->get('theme'))->toBe('light');
    });
});
