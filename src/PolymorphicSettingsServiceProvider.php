<?php

namespace SiteSource\PolymorphicSettings;

use SiteSource\PolymorphicSettings\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PolymorphicSettingsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('polymorphic-settings')
            ->hasConfigFile()
            ->hasMigration('create_polymorphic_settings_table')
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PolymorphicSettings::class);
        $this->app->alias(PolymorphicSettings::class, 'polymorphic-settings');
    }
}
