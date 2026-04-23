<?php

use SiteSource\PolymorphicSettings\SettingsStore;
use SiteSource\PolymorphicSettings\Tests\Support\TestTeam;

beforeEach(function () {
    $this->team = TestTeam::create(['name' => 'Acme']);
    $this->otherTeam = TestTeam::create(['name' => 'Globex']);

    $this->store = new SettingsStore(
        configurableType: $this->team->getMorphClass(),
        configurableId: (string) $this->team->id,
    );

    $this->otherStore = new SettingsStore(
        configurableType: $this->otherTeam->getMorphClass(),
        configurableId: (string) $this->otherTeam->id,
    );

    $this->globalStore = new SettingsStore;
});

it('reports itself as not global', function () {
    expect($this->store->isGlobal())->toBeFalse();
});

it('isolates settings between scopes with the same key', function () {
    $this->store->put('theme', 'dark');
    $this->otherStore->put('theme', 'light');
    $this->globalStore->put('theme', 'auto');

    expect($this->store->get('theme'))->toBe('dark');
    expect($this->otherStore->get('theme'))->toBe('light');
    expect($this->globalStore->get('theme'))->toBe('auto');
});

it('all() returns only settings in this scope', function () {
    $this->store->putMany(['theme' => 'dark', 'locale' => 'en-US']);
    $this->otherStore->putMany(['theme' => 'light']);
    $this->globalStore->put('site_title', 'Acme');

    // all() returns alphabetically ordered by key.
    expect($this->store->all())->toBe([
        'locale' => 'en-US',
        'theme' => 'dark',
    ]);
});

it('forget() in one scope does not affect other scopes', function () {
    $this->store->put('theme', 'dark');
    $this->otherStore->put('theme', 'light');

    $this->store->forget('theme');

    expect($this->store->has('theme'))->toBeFalse();
    expect($this->otherStore->get('theme'))->toBe('light');
});

it('has() is scope-aware', function () {
    $this->store->put('theme', 'dark');

    expect($this->store->has('theme'))->toBeTrue();
    expect($this->otherStore->has('theme'))->toBeFalse();
});

it('getMany is scope-aware', function () {
    $this->store->putMany(['a' => 1, 'b' => 2]);
    $this->otherStore->putMany(['a' => 10, 'b' => 20]);

    expect($this->store->getMany(['a', 'b']))->toBe(['a' => 1, 'b' => 2]);
    expect($this->otherStore->getMany(['a', 'b']))->toBe(['a' => 10, 'b' => 20]);
});

it('pull in one scope does not affect other scopes', function () {
    $this->store->put('key', 'mine');
    $this->otherStore->put('key', 'theirs');

    expect($this->store->pull('key'))->toBe('mine');
    expect($this->store->has('key'))->toBeFalse();
    expect($this->otherStore->get('key'))->toBe('theirs');
});

it('handles UUID-style string IDs', function () {
    $store = new SettingsStore(
        configurableType: 'App\\Models\\User',
        configurableId: '550e8400-e29b-41d4-a716-446655440000',
    );

    $store->put('theme', 'dark');

    expect($store->get('theme'))->toBe('dark');
});
