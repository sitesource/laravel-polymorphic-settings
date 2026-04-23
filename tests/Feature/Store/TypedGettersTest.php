<?php

use SiteSource\PolymorphicSettings\SettingsStore;

beforeEach(function () {
    $this->store = new SettingsStore;
});

describe('string()', function () {
    it('returns the string when the value is a string', function () {
        $this->store->put('title', 'Acme');
        expect($this->store->string('title'))->toBe('Acme');
    });

    it('returns the default when the value is not a string', function () {
        $this->store->put('count', 42);
        expect($this->store->string('count', 'fallback'))->toBe('fallback');
    });

    it('returns null default for missing keys', function () {
        expect($this->store->string('missing'))->toBeNull();
    });
});

describe('int()', function () {
    it('returns the int when the value is an int', function () {
        $this->store->put('count', 42);
        expect($this->store->int('count'))->toBe(42);
    });

    it('does not coerce strings to int', function () {
        $this->store->put('count', '42');
        expect($this->store->int('count', 0))->toBe(0);
    });

    it('does not coerce floats to int', function () {
        $this->store->put('count', 42.5);
        expect($this->store->int('count', 0))->toBe(0);
    });
});

describe('float()', function () {
    it('returns the float when the value is a float', function () {
        $this->store->put('rate', 1.5);
        expect($this->store->float('rate'))->toBe(1.5);
    });

    it('widens int to float', function () {
        $this->store->put('count', 42);
        expect($this->store->float('count'))->toBe(42.0);
    });

    it('does not coerce strings to float', function () {
        $this->store->put('rate', '1.5');
        expect($this->store->float('rate', 0.0))->toBe(0.0);
    });
});

describe('bool()', function () {
    it('returns the bool when the value is a bool', function () {
        $this->store->put('enabled', true);
        $this->store->put('disabled', false);

        expect($this->store->bool('enabled'))->toBeTrue();
        expect($this->store->bool('disabled'))->toBeFalse();
    });

    it('does not coerce truthy strings to bool', function () {
        $this->store->put('enabled', 'yes');
        expect($this->store->bool('enabled', false))->toBeFalse();
    });

    it('does not coerce 1/0 to bool', function () {
        $this->store->put('enabled', 1);
        expect($this->store->bool('enabled', false))->toBeFalse();
    });
});

describe('array()', function () {
    it('returns the array when the value is an array', function () {
        $this->store->put('tags', ['a', 'b', 'c']);
        expect($this->store->array('tags'))->toBe(['a', 'b', 'c']);
    });

    it('returns the default when the value is not an array', function () {
        $this->store->put('tags', 'abc');
        expect($this->store->array('tags', []))->toBe([]);
    });
});
