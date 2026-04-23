<?php

use SiteSource\PolymorphicSettings\SettingsStore;

beforeEach(function () {
    $this->store = new SettingsStore;
});

it('reads a top-level nested key with dot-notation', function () {
    $this->store->put('theme', ['mode' => 'dark', 'accent' => '#f00']);

    expect($this->store->get('theme.mode'))->toBe('dark');
    expect($this->store->get('theme.accent'))->toBe('#f00');
});

it('reads a deeply nested value with dot-notation', function () {
    $this->store->put('theme', [
        'palette' => [
            'primary' => ['hex' => '#abc123'],
        ],
    ]);

    expect($this->store->get('theme.palette.primary.hex'))->toBe('#abc123');
});

it('returns default when the nested path is missing', function () {
    $this->store->put('theme', ['mode' => 'dark']);

    expect($this->store->get('theme.accent'))->toBeNull();
    expect($this->store->get('theme.accent', 'gray'))->toBe('gray');
});

it('returns default when the root key is missing', function () {
    expect($this->store->get('missing.path', 'fallback'))->toBe('fallback');
});

it('returns default when traversing into a scalar', function () {
    $this->store->put('theme', 'dark');

    expect($this->store->get('theme.mode', 'fallback'))->toBe('fallback');
});

it('does not split keys that do not contain dots', function () {
    $this->store->put('not_nested', 'value');

    expect($this->store->get('not_nested'))->toBe('value');
});

it('treats keys with literal dots as root key + path', function () {
    // Users storing keys with literal dots in the name will find them
    // interpreted as nested paths. This is a known tradeoff of dot-notation
    // support. Keys should not contain dots unless nesting is intended.
    $this->store->put('app.name', 'whole value');

    // Root 'app' doesn't exist, so 'app.name' returns default.
    expect($this->store->get('app.name'))->toBeNull();
});
