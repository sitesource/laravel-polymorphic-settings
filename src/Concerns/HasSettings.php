<?php

namespace SiteSource\PolymorphicSettings\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use SiteSource\PolymorphicSettings\Models\Setting;
use SiteSource\PolymorphicSettings\PolymorphicSettings;
use SiteSource\PolymorphicSettings\SettingsStore;

/**
 * Adds scoped settings access to any Eloquent model.
 *
 * Usage:
 *
 *     class Team extends Model
 *     {
 *         use HasSettings;
 *
 *         // Opt in to purging settings when the team is deleted.
 *         public bool $cascadeDeleteSettings = true;
 *     }
 *
 *     $team->settings()->put('theme', 'dark');
 *     $team->settings()->get('theme');           // 'dark'
 *
 *     // Eager load every setting attached to a team.
 *     Team::with('scopedSettings')->get();
 *
 * On deletion, settings are purged only when:
 *   - $cascadeDeleteSettings is true on the model, AND
 *   - the delete is a real delete (for soft-delete models, only
 *     isForceDeleting() triggers cleanup — soft-deleted rows keep
 *     their settings so restore() works as expected).
 *
 * @mixin Model
 */
trait HasSettings
{
    protected static function bootHasSettings(): void
    {
        static::deleted(function (Model $model) {
            if (! self::modelOptsIntoCascade($model)) {
                return;
            }

            // Soft-delete-aware: if the model uses SoftDeletes and this
            // deletion was a soft delete (not force delete), leave
            // settings in place so they survive a restore().
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            app(PolymorphicSettings::class)->for($model)->forgetAll();
        });
    }

    private static function modelOptsIntoCascade(Model $model): bool
    {
        return property_exists($model, 'cascadeDeleteSettings')
            && $model->cascadeDeleteSettings === true;
    }

    public function settings(): SettingsStore
    {
        return app(PolymorphicSettings::class)->for($this);
    }

    public function scopedSettings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'configurable');
    }
}
