<?php

namespace SiteSource\PolymorphicSettings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SiteSource\PolymorphicSettings\PolymorphicSettings
 */
class PolymorphicSettings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SiteSource\PolymorphicSettings\PolymorphicSettings::class;
    }
}
