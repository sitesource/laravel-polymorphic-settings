<?php

namespace SiteSource\PolymorphicSettings;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use SiteSource\PolymorphicSettings\Commands\PolymorphicSettingsCommand;

class PolymorphicSettingsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('polymorphic-settings')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_polymorphic_settings_table')
            ->hasCommand(PolymorphicSettingsCommand::class);
    }
}
