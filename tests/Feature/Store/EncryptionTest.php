<?php

use Illuminate\Support\Facades\DB;
use SiteSource\PolymorphicSettings\Models\Setting;
use SiteSource\PolymorphicSettings\SettingsStore;

beforeEach(function () {
    $this->store = new SettingsStore;
});

it('roundtrips encrypted string values', function () {
    $this->store->put('api_key', 'sk-secret-abc', encrypted: true);

    expect($this->store->get('api_key'))->toBe('sk-secret-abc');
});

it('roundtrips encrypted array values', function () {
    $this->store->put('oauth', [
        'client_id' => 'abc',
        'client_secret' => 'super-secret',
    ], encrypted: true);

    expect($this->store->get('oauth'))->toBe([
        'client_id' => 'abc',
        'client_secret' => 'super-secret',
    ]);
});

it('never stores encrypted values in plaintext in the database', function () {
    $this->store->put('password', 'hunter2', encrypted: true);

    $rawValue = DB::table('polymorphic_settings')->where('key', 'password')->value('value');

    // DB column stores JSON-encoded cipher text; should contain neither the
    // plaintext value nor any recognizable prefix.
    expect($rawValue)->not->toContain('hunter2');
});

it('marks encrypted settings with the encrypted flag', function () {
    $this->store->put('secret', 'shh', encrypted: true);
    $this->store->put('public', 'visible');

    $secret = Setting::where('key', 'secret')->first();
    $public = Setting::where('key', 'public')->first();

    expect($secret->encrypted)->toBeTrue();
    expect($public->encrypted)->toBeFalse();
});

it('overwrites an encrypted setting with a plain setting', function () {
    $this->store->put('field', 'encrypted-value', encrypted: true);
    $this->store->put('field', 'now-plain');

    expect($this->store->get('field'))->toBe('now-plain');
    expect(Setting::where('key', 'field')->first()->encrypted)->toBeFalse();
});

it('overwrites a plain setting with an encrypted setting', function () {
    $this->store->put('field', 'plain');
    $this->store->put('field', 'encrypted-now', encrypted: true);

    expect($this->store->get('field'))->toBe('encrypted-now');
    expect(Setting::where('key', 'field')->first()->encrypted)->toBeTrue();
});

it('has() works for encrypted settings', function () {
    $this->store->put('secret', 'value', encrypted: true);

    expect($this->store->has('secret'))->toBeTrue();
});

it('forget() works for encrypted settings', function () {
    $this->store->put('secret', 'value', encrypted: true);

    expect($this->store->forget('secret'))->toBeTrue();
    expect($this->store->get('secret'))->toBeNull();
});

it('all() decrypts encrypted entries', function () {
    $this->store->put('plain', 'hello');
    $this->store->put('secret', 'classified', encrypted: true);

    expect($this->store->all())->toBe([
        'plain' => 'hello',
        'secret' => 'classified',
    ]);
});

it('getMany() decrypts encrypted entries', function () {
    $this->store->put('a', 'plain-a');
    $this->store->put('b', 'classified-b', encrypted: true);

    expect($this->store->getMany(['a', 'b']))->toBe([
        'a' => 'plain-a',
        'b' => 'classified-b',
    ]);
});

it('typed getters work on encrypted values', function () {
    $this->store->put('key', 'string-value', encrypted: true);
    $this->store->put('count', 42, encrypted: true);
    $this->store->put('tags', ['a', 'b'], encrypted: true);

    expect($this->store->string('key'))->toBe('string-value');
    expect($this->store->int('count'))->toBe(42);
    expect($this->store->array('tags'))->toBe(['a', 'b']);
});

it('dot-notation works on encrypted array values', function () {
    $this->store->put('config', [
        'database' => ['host' => 'localhost', 'port' => 5432],
    ], encrypted: true);

    expect($this->store->get('config.database.host'))->toBe('localhost');
    expect($this->store->get('config.database.port'))->toBe(5432);
});

it('updateValues preserves the encrypted flag on an encrypted setting', function () {
    $this->store->put('secret', ['a' => 1, 'b' => 2], encrypted: true);
    $this->store->updateValues('secret', ['b' => 20, 'c' => 3]);

    expect($this->store->get('secret'))->toBe([
        'a' => 1,
        'b' => 20,
        'c' => 3,
    ]);
    expect(Setting::where('key', 'secret')->first()->encrypted)->toBeTrue();
});

it('pull works for encrypted settings', function () {
    $this->store->put('secret', 'classified', encrypted: true);

    expect($this->store->pull('secret'))->toBe('classified');
    expect($this->store->has('secret'))->toBeFalse();
});

it('putMany can mark a batch as encrypted', function () {
    $this->store->putMany([
        'api_key' => 'sk-abc',
        'api_secret' => 'super-secret',
    ], encrypted: true);

    $keys = Setting::whereIn('key', ['api_key', 'api_secret'])->get();

    expect($keys)->toHaveCount(2);
    foreach ($keys as $setting) {
        expect($setting->encrypted)->toBeTrue();
    }
});

it('encrypted null stores as null without calling encrypt()', function () {
    $this->store->put('nullable', null, encrypted: true);

    $raw = DB::table('polymorphic_settings')->where('key', 'nullable')->first();

    expect($raw->value)->toBeNull();
    expect((bool) $raw->encrypted)->toBeTrue();
    expect($this->store->get('nullable'))->toBeNull();
});
