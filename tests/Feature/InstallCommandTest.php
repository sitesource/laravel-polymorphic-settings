<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

it('registers the install command with artisan', function () {
    expect(Artisan::all())->toHaveKey('polymorphic-settings:install');
});

it('has a description that mentions polymorphic settings', function () {
    expect(Artisan::all()['polymorphic-settings:install']->getDescription())
        ->toContain('polymorphic settings');
});

it('runs end-to-end non-interactively, publishing and migrating', function () {
    // Our TestCase eagerly runs the package migration. Drop the table so
    // the install command's migrate step has real work to do.
    Schema::dropIfExists('polymorphic_settings');

    $this->artisan('polymorphic-settings:install', [
        '--no-interaction' => true,
        '--force' => true,
    ])->assertSuccessful();

    expect(Schema::hasTable('polymorphic_settings'))->toBeTrue();
});
