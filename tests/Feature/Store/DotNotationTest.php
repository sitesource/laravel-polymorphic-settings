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

it('roundtrips a literal key that contains dots', function () {
    // Regression test: before v0.1.2, put() stored literal 'commerce.foo'
    // but get() always split on dots and looked inside a 'commerce' array,
    // so the write silently disappeared. Reads now try a literal match
    // first and only fall back to nested traversal if the literal key
    // does not exist.
    $this->store->put('commerce.foo', true);

    expect($this->store->get('commerce.foo'))->toBeTrue();
});

it('roundtrips deep namespaced literal keys', function () {
    $this->store->put('commerce.stripe.klarna.enabled', true);
    $this->store->put('commerce.stripe.afterpay.enabled', false);

    expect($this->store->get('commerce.stripe.klarna.enabled'))->toBeTrue();
    expect($this->store->get('commerce.stripe.afterpay.enabled'))->toBeFalse();
});

it('prefers a literal match over a nested-path interpretation', function () {
    // If both `commerce` (array with `foo` inside) AND `commerce.foo`
    // (literal) exist, the literal wins. This is the simplest,
    // least-surprising rule to reason about; callers who want the
    // nested form should not also store a colliding literal.
    $this->store->put('commerce', ['foo' => 'from-nested']);
    $this->store->put('commerce.foo', 'from-literal');

    expect($this->store->get('commerce.foo'))->toBe('from-literal');
});

it('falls back to nested traversal when no literal key exists', function () {
    $this->store->put('commerce', ['foo' => 'nested-value']);

    // No literal row with key='commerce.foo' → fall back to nested read.
    expect($this->store->get('commerce.foo'))->toBe('nested-value');
});

it('forget targets only the literal key, never nested paths', function () {
    // forget() is literal-only. Forgetting 'commerce.foo' when only
    // 'commerce' exists should be a no-op, not mutate the commerce array.
    $this->store->put('commerce', ['foo' => 'nested-value']);

    expect($this->store->forget('commerce.foo'))->toBeFalse();
    expect($this->store->get('commerce.foo'))->toBe('nested-value');
    expect($this->store->get('commerce'))->toBe(['foo' => 'nested-value']);
});

it('has() is literal-only, never reports nested path existence', function () {
    $this->store->put('commerce', ['foo' => 'nested-value']);

    // has('commerce.foo') asks about the literal key — not whether
    // the nested path is reachable.
    expect($this->store->has('commerce'))->toBeTrue();
    expect($this->store->has('commerce.foo'))->toBeFalse();
});
