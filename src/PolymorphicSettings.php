<?php

namespace SiteSource\PolymorphicSettings;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class PolymorphicSettings
{
    public function global(): SettingsStore
    {
        return new SettingsStore;
    }

    public function for(Model $model): SettingsStore
    {
        if (! $model->exists) {
            throw new InvalidArgumentException(
                'Cannot bind settings to an unsaved model. Call save() first, or use a model loaded from the database.'
            );
        }

        return new SettingsStore(
            configurableType: $model->getMorphClass(),
            configurableId: (string) $model->getKey(),
        );
    }
}
