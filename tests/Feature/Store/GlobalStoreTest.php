<?php

use SiteSource\PolymorphicSettings\Models\Setting;
use SiteSource\PolymorphicSettings\SettingsStore;

beforeEach(function () {
    $this->store = new SettingsStore;
});

it('reports itself as global', function () {
    expect($this->store->isGlobal())->toBeTrue();
});

it('returns null for missing keys', function () {
    expect($this->store->get('missing'))->toBeNull();
});

it('returns the provided default for missing keys', function () {
    expect($this->store->get('missing', 'fallback'))->toBe('fallback');
});

it('roundtrips string values', function () {
    $this->store->put('title', 'Acme');
    expect($this->store->get('title'))->toBe('Acme');
});

it('roundtrips integer values', function () {
    $this->store->put('max_members', 42);
    expect($this->store->get('max_members'))->toBe(42);
});

it('roundtrips float values', function () {
    $this->store->put('rate', 1.5);
    expect($this->store->get('rate'))->toBe(1.5);
});

it('roundtrips boolean values', function () {
    $this->store->put('enabled', true);
    $this->store->put('disabled', false);

    expect($this->store->get('enabled'))->toBeTrue();
    expect($this->store->get('disabled'))->toBeFalse();
});

it('roundtrips array values', function () {
    $this->store->put('theme', ['mode' => 'dark', 'accent' => '#f00']);

    expect($this->store->get('theme'))->toBe([
        'mode' => 'dark',
        'accent' => '#f00',
    ]);
});

it('roundtrips null values', function () {
    $this->store->put('nothing', null);

    expect($this->store->get('nothing'))->toBeNull();
    expect($this->store->has('nothing'))->toBeTrue();
});

it('overwrites existing values on put', function () {
    $this->store->put('key', 'first');
    $this->store->put('key', 'second');

    expect($this->store->get('key'))->toBe('second');
    expect(Setting::where('key', 'key')->count())->toBe(1);
});

it('checks existence with has()', function () {
    $this->store->put('key', 'value');

    expect($this->store->has('key'))->toBeTrue();
    expect($this->store->has('other'))->toBeFalse();
});

it('forgets a key and reports whether it existed', function () {
    $this->store->put('key', 'value');

    expect($this->store->forget('key'))->toBeTrue();
    expect($this->store->has('key'))->toBeFalse();
    expect($this->store->forget('key'))->toBeFalse();
});

it('returns all settings in the scope as key => value', function () {
    $this->store->put('one', 1);
    $this->store->put('two', 2);
    $this->store->put('three', 3);

    expect($this->store->all())->toBe([
        'one' => 1,
        'three' => 3,
        'two' => 2,
    ]);
});

it('all() is ordered alphabetically by key for deterministic output', function () {
    $this->store->put('zebra', 1);
    $this->store->put('apple', 2);
    $this->store->put('mango', 3);

    expect(array_keys($this->store->all()))->toBe(['apple', 'mango', 'zebra']);
});

it('putMany inserts every key', function () {
    $this->store->putMany([
        'a' => 1,
        'b' => 2,
        'c' => 3,
    ]);

    expect($this->store->all())->toBe([
        'a' => 1,
        'b' => 2,
        'c' => 3,
    ]);
});

it('getMany returns requested keys with null for missing', function () {
    $this->store->put('a', 1);
    $this->store->put('c', 3);

    expect($this->store->getMany(['a', 'b', 'c']))->toBe([
        'a' => 1,
        'b' => null,
        'c' => 3,
    ]);
});

it('getMany preserves the requested key order', function () {
    $this->store->putMany(['a' => 1, 'b' => 2, 'c' => 3]);

    expect(array_keys($this->store->getMany(['c', 'a', 'b'])))->toBe(['c', 'a', 'b']);
});

it('pull returns the value and removes the key', function () {
    $this->store->put('key', 'value');

    expect($this->store->pull('key'))->toBe('value');
    expect($this->store->has('key'))->toBeFalse();
});

it('pull returns the default for missing keys without creating them', function () {
    expect($this->store->pull('missing', 'fallback'))->toBe('fallback');
    expect($this->store->has('missing'))->toBeFalse();
});

it('updateValues merges array values', function () {
    $this->store->put('theme', ['mode' => 'dark', 'accent' => '#f00']);
    $this->store->updateValues('theme', ['accent' => '#0f0', 'size' => 'lg']);

    expect($this->store->get('theme'))->toBe([
        'mode' => 'dark',
        'accent' => '#0f0',
        'size' => 'lg',
    ]);
});

it('updateValues creates the key if it does not exist', function () {
    $this->store->updateValues('theme', ['mode' => 'light']);

    expect($this->store->get('theme'))->toBe(['mode' => 'light']);
});

it('updateValues overwrites non-array existing values', function () {
    $this->store->put('theme', 'dark');
    $this->store->updateValues('theme', ['mode' => 'light']);

    expect($this->store->get('theme'))->toBe(['mode' => 'light']);
});

it('operations chain fluently', function () {
    $result = $this->store
        ->put('a', 1)
        ->put('b', 2)
        ->putMany(['c' => 3, 'd' => 4])
        ->updateValues('theme', ['mode' => 'dark']);

    expect($result)->toBe($this->store);
    expect($this->store->all())->toHaveCount(5);
});
