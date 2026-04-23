<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use SiteSource\PolymorphicSettings\SettingsStore;
use SiteSource\PolymorphicSettings\Tests\Support\TestTeam;

beforeEach(function () {
    Cache::flush();
    $this->store = new SettingsStore;
});

function countQueriesWhile(callable $fn): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $fn();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

describe('with cache enabled (default)', function () {
    it('caches a get() result after the first call', function () {
        $this->store->put('theme', 'dark');

        $firstCount = countQueriesWhile(fn () => $this->store->get('theme'));
        $secondCount = countQueriesWhile(fn () => $this->store->get('theme'));
        $thirdCount = countQueriesWhile(fn () => $this->store->get('theme'));

        expect($firstCount)->toBeGreaterThan(0);
        expect($secondCount)->toBe(0);
        expect($thirdCount)->toBe(0);
    });

    it('preserves null values through the cache', function () {
        $this->store->put('nullable', null);

        // Prime the cache.
        $this->store->get('nullable');

        $count = countQueriesWhile(
            fn () => expect($this->store->get('nullable'))->toBeNull()
        );

        expect($count)->toBe(0);
    });

    it('invalidates the cache on put', function () {
        $this->store->put('theme', 'dark');
        $this->store->get('theme'); // prime cache

        $this->store->put('theme', 'light');

        expect($this->store->get('theme'))->toBe('light');
    });

    it('invalidates the cache on forget', function () {
        $this->store->put('theme', 'dark');
        $this->store->get('theme'); // prime cache

        $this->store->forget('theme');

        expect($this->store->get('theme'))->toBeNull();
    });

    it('does not cache misses', function () {
        // First call fetches from DB.
        $first = countQueriesWhile(fn () => $this->store->get('missing'));
        // Second call still fetches from DB (misses are not cached).
        $second = countQueriesWhile(fn () => $this->store->get('missing'));

        expect($first)->toBeGreaterThan(0);
        expect($second)->toBeGreaterThan(0);
    });

    it('scopes cache keys per scope', function () {
        $teamA = TestTeam::create(['name' => 'A']);
        $teamB = TestTeam::create(['name' => 'B']);

        $storeA = new SettingsStore($teamA->getMorphClass(), (string) $teamA->id);
        $storeB = new SettingsStore($teamB->getMorphClass(), (string) $teamB->id);

        $storeA->put('theme', 'dark');
        $storeB->put('theme', 'light');

        // Priming A should not prime B.
        $storeA->get('theme');

        expect($storeB->get('theme'))->toBe('light');
        expect($storeA->get('theme'))->toBe('dark');
    });

    it('uses the configured cache prefix', function () {
        config()->set('polymorphic-settings.cache.prefix', 'my-settings-ns');

        $this->store->put('theme', 'dark');
        $this->store->get('theme'); // prime cache

        expect(Cache::has('my-settings-ns:*:theme'))->toBeTrue();
        expect(Cache::has('polymorphic-settings:*:theme'))->toBeFalse();
    });

    it('caches forever by default when TTL is null', function () {
        $this->store->put('theme', 'dark');
        $this->store->get('theme'); // prime cache

        // Travel far into the future — forever cache should survive.
        $this->travel(10)->years();

        expect(Cache::has('polymorphic-settings:*:theme'))->toBeTrue();
    });

    it('expires the cache entry after the configured TTL', function () {
        config()->set('polymorphic-settings.cache.ttl', 60); // 1 minute

        $this->store->put('theme', 'dark');
        $this->store->get('theme'); // prime cache

        expect(Cache::has('polymorphic-settings:*:theme'))->toBeTrue();

        $this->travel(2)->minutes();

        expect(Cache::has('polymorphic-settings:*:theme'))->toBeFalse();
    });
});

describe('with cache disabled', function () {
    beforeEach(function () {
        config()->set('polymorphic-settings.cache.enabled', false);
    });

    it('hits the database on every get()', function () {
        $this->store->put('theme', 'dark');

        $first = countQueriesWhile(fn () => $this->store->get('theme'));
        $second = countQueriesWhile(fn () => $this->store->get('theme'));
        $third = countQueriesWhile(fn () => $this->store->get('theme'));

        expect($first)->toBeGreaterThan(0);
        expect($second)->toBeGreaterThan(0);
        expect($third)->toBeGreaterThan(0);
    });

    it('does not write to the cache', function () {
        $this->store->put('theme', 'dark');
        $this->store->get('theme');

        expect(Cache::has('polymorphic-settings:*:theme'))->toBeFalse();
    });
});

describe('encrypted settings with cache', function () {
    it('caches decrypted values on the first get', function () {
        $this->store->put('secret', 'classified', encrypted: true);

        $first = countQueriesWhile(fn () => $this->store->get('secret'));
        $second = countQueriesWhile(fn () => expect($this->store->get('secret'))->toBe('classified'));

        expect($first)->toBeGreaterThan(0);
        expect($second)->toBe(0);
    });
});
