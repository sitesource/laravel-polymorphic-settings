<?php

namespace SiteSource\PolymorphicSettings\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Setting extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'value' => 'array',
        'encrypted' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('polymorphic-settings.table', 'polymorphic_settings');
    }

    public function getKeyType(): string
    {
        return $this->usesUuidKeys() ? 'string' : 'int';
    }

    public function getIncrementing(): bool
    {
        return ! $this->usesUuidKeys();
    }

    public function uniqueIds(): array
    {
        return $this->usesUuidKeys() ? ['id'] : [];
    }

    public function configurable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function usesUuidKeys(): bool
    {
        return config('polymorphic-settings.key_type') === 'uuid';
    }
}
