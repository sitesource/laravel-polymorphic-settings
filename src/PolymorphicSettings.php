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
        if ($model->getKey() === null) {
            throw new InvalidArgumentException(
                'Cannot bind settings to a model without a primary key. Call save() first, or use a model loaded from the database.'
            );
        }

        return new SettingsStore(
            configurableType: $model->getMorphClass(),
            configurableId: (string) $model->getKey(),
        );
    }
}
