<?php

namespace SiteSource\PolymorphicSettings\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int|string $id
 * @property string $key
 * @property mixed $value
 * @property bool $encrypted
 * @property string|null $configurable_id
 * @property string|null $configurable_type
 * @property string $scope_key
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Model|null $configurable
 */
class Setting extends Model
{
    use HasUuids;

    public const GLOBAL_SCOPE_KEY = '*';

    protected $guarded = [];

    protected $hidden = [
        'scope_key',
    ];

    protected $casts = [
        'value' => 'array',
        'encrypted' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $setting) {
            $setting->scope_key = $setting->computeScopeKey();
        });
    }

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

    public function isGlobal(): bool
    {
        return $this->scope_key === self::GLOBAL_SCOPE_KEY;
    }

    public function computeScopeKey(): string
    {
        $type = $this->configurable_type;
        $id = $this->configurable_id;

        if ($type === null || $type === '' || $id === null || $id === '') {
            return self::GLOBAL_SCOPE_KEY;
        }

        return $type.':'.$id;
    }

    protected function usesUuidKeys(): bool
    {
        return config('polymorphic-settings.key_type') === 'uuid';
    }
}
