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

describe('cache hygiene for dotted keys', function () {
    it('does not cache literal lookups for dotted keys', function () {
        // Reported by Kimber v2 — caching the literal-row lookup of a
        // dotted key meant the entry survived a put() on the root,
        // so a subsequent dotted read served the stale literal forever.
        $this->store->put('commerce.foo', true);
        expect($this->store->get('commerce.foo'))->toBeTrue();

        // Simulate an external mutation that the SettingsStore has no
        // way to know about — e.g. a sibling process or a manual SQL
        // deletion. The cache must NOT have made the literal lookup
        // sticky.
        DB::table('polymorphic_settings')->where('key', 'commerce.foo')->delete();

        expect($this->store->get('commerce.foo'))->toBeNull();
    });

    it('serves the new nested value after migrating from literal to nested storage', function () {
        // Same scenario, but with the canonical migration: row deleted,
        // root key re-put with a nested array. After the fix, the
        // dotted read should resolve via the (cache-fresh) root, not
        // the (stale) literal cache entry.
        $this->store->put('commerce.foo', true);
        expect($this->store->get('commerce.foo'))->toBeTrue();

        DB::table('polymorphic_settings')->where('key', 'commerce.foo')->delete();
        $this->store->put('commerce', ['foo' => false]);

        expect($this->store->get('commerce.foo'))->toBeFalse();
    });

    it('still caches the root for dotted reads (perf)', function () {
        // Caching is dropped for the literal lookup of a dotted key,
        // but the root traversal remains cached — repeated dot-notation
        // reads against the same root should not multiply DB queries.
        $this->store->put('theme', ['mode' => 'dark', 'accent' => '#f00']);

        // Prime: one query for the literal miss + one for the root.
        $this->store->get('theme.mode');

        // Subsequent dotted reads against any field of the same root:
        // one literal-miss query each, but root stays warm.
        $second = countQueriesWhile(fn () => $this->store->get('theme.mode'));
        $third = countQueriesWhile(fn () => $this->store->get('theme.accent'));

        expect($second)->toBeLessThanOrEqual(1);
        expect($third)->toBeLessThanOrEqual(1);
    });
});

describe('forget() cache invalidation', function () {
    it('busts the cache even when the row was already gone', function () {
        // Reported by Kimber v2 — forget() guarded cacheForget() behind
        // `if ($deleted)`, so calling it after an external row deletion
        // left the stale cache entry in place and gave the application
        // no public API to clean up.
        $this->store->put('foo', 'bar');
        $this->store->get('foo'); // primes cache
        expect(Cache::has('polymorphic-settings:*:foo'))->toBeTrue();

        // External deletion leaves the cache holding 'bar'.
        DB::table('polymorphic_settings')->where('key', 'foo')->delete();
        expect(Cache::has('polymorphic-settings:*:foo'))->toBeTrue();

        // forget() returns false because no row was deleted, but the
        // cache must be invalidated regardless.
        expect($this->store->forget('foo'))->toBeFalse();
        expect(Cache::has('polymorphic-settings:*:foo'))->toBeFalse();
        expect($this->store->get('foo'))->toBeNull();
    });

    it('returns true and busts the cache when a row was deleted', function () {
        $this->store->put('foo', 'bar');
        $this->store->get('foo');

        expect($this->store->forget('foo'))->toBeTrue();
        expect(Cache::has('polymorphic-settings:*:foo'))->toBeFalse();
    });
});
