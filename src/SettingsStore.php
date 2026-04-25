<?php

namespace SiteSource\PolymorphicSettings;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use SiteSource\PolymorphicSettings\Models\Setting;

class SettingsStore
{
    public function __construct(
        protected readonly ?string $configurableType = null,
        protected readonly ?string $configurableId = null,
    ) {}

    public function isGlobal(): bool
    {
        return $this->configurableType === null && $this->configurableId === null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $isDotted = str_contains($key, '.');

        // Try a literal-key lookup first (preserves the v0.1.2 contract
        // that put('a.b', x) → get('a.b') roundtrips). Skip the cache
        // layer for dotted keys: cache invalidation runs against the
        // root key when put('a', ...) is called, but a literal cache
        // entry under 'a.b' would never be busted by that call. To
        // avoid silently serving a stale literal after the user has
        // migrated the same logical setting under a nested root, the
        // literal path stays uncached for dotted keys. The
        // root-traversal path below is still cached.
        $resolved = $isDotted
            ? $this->fetchValue($key)
            : $this->resolveValue($key);

        if ($resolved !== null) {
            return $resolved['value'];
        }

        // Fall back to dot-notation traversal only when the key is
        // dotted and no literal row matched.
        if ($isDotted) {
            [$rootKey, $nestedPath] = $this->splitKey($key);

            $resolved = $this->resolveValue($rootKey);

            if ($resolved === null) {
                return $default;
            }

            $value = $resolved['value'];

            if (! is_array($value)) {
                return $default;
            }

            return Arr::get($value, $nestedPath, $default);
        }

        return $default;
    }

    public function put(string $key, mixed $value, bool $encrypted = false): self
    {
        $storedValue = $encrypted && $value !== null ? encrypt($value) : $value;

        $this->query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'encrypted' => $encrypted,
                'configurable_type' => $this->configurableType,
                'configurable_id' => $this->configurableId,
            ],
        );

        $this->cacheForget($key);

        return $this;
    }

    public function putMany(array $values, bool $encrypted = false): self
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value, $encrypted);
        }

        return $this;
    }

    public function forget(string $key): bool
    {
        $deleted = $this->query()->where('key', $key)->delete() > 0;

        // Bust the cache regardless of whether the row existed in the
        // database. External mutations (a sibling process, raw SQL,
        // a previous run that deleted the row outside this store) can
        // leave a stale cache entry that the application has no other
        // public API to invalidate.
        $this->cacheForget($key);

        return $deleted;
    }

    public function forgetAll(): int
    {
        if ($this->cacheEnabled()) {
            foreach ($this->query()->pluck('key') as $key) {
                $this->cacheForget($key);
            }
        }

        return $this->query()->delete();
    }

    public function has(string $key): bool
    {
        return $this->query()->where('key', $key)->exists();
    }

    public function all(): array
    {
        return $this->query()
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn (Setting $setting) => [
                $setting->key => $this->unwrap($setting),
            ])
            ->all();
    }

    public function getMany(array $keys): array
    {
        $rows = $this->query()
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');

        $results = [];
        foreach ($keys as $key) {
            $setting = $rows->get($key);
            $results[$key] = $setting instanceof Setting ? $this->unwrap($setting) : null;
        }

        return $results;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    public function updateValues(string $key, array $values): self
    {
        $setting = $this->query()->where('key', $key)->first();

        if ($setting === null) {
            return $this->put($key, $values);
        }

        $existingValue = $this->unwrap($setting);
        $merged = is_array($existingValue)
            ? array_merge($existingValue, $values)
            : $values;

        return $this->put($key, $merged, encrypted: $setting->encrypted);
    }

    public function string(string $key, ?string $default = null): ?string
    {
        $value = $this->get($key);

        return is_string($value) ? $value : $default;
    }

    public function int(string $key, ?int $default = null): ?int
    {
        $value = $this->get($key);

        return is_int($value) ? $value : $default;
    }

    public function float(string $key, ?float $default = null): ?float
    {
        $value = $this->get($key);

        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        return $default;
    }

    public function bool(string $key, ?bool $default = null): ?bool
    {
        $value = $this->get($key);

        return is_bool($value) ? $value : $default;
    }

    public function array(string $key, ?array $default = null): ?array
    {
        $value = $this->get($key);

        return is_array($value) ? $value : $default;
    }

    /**
     * @return Builder<Setting>
     */
    protected function query(): Builder
    {
        return Setting::query()->where('scope_key', $this->scopeKey());
    }

    protected function scopeKey(): string
    {
        return $this->isGlobal()
            ? Setting::GLOBAL_SCOPE_KEY
            : $this->configurableType.':'.$this->configurableId;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    protected function splitKey(string $key): array
    {
        if (str_contains($key, '.')) {
            [$root, $path] = explode('.', $key, 2);

            return [$root, $path];
        }

        return [$key, null];
    }

    protected function unwrap(Setting $setting): mixed
    {
        $value = $setting->value;

        if ($setting->encrypted && $value !== null) {
            return decrypt($value);
        }

        return $value;
    }

    /**
     * @return array{value: mixed}|null
     */
    protected function resolveValue(string $rootKey): ?array
    {
        if (! $this->cacheEnabled()) {
            return $this->fetchValue($rootKey);
        }

        $cacheKey = $this->cacheKey($rootKey);
        $cached = $this->cacheStore()->get($cacheKey);

        if (is_array($cached) && array_key_exists('value', $cached)) {
            return $cached;
        }

        $result = $this->fetchValue($rootKey);

        if ($result !== null) {
            $ttl = $this->cacheTtl();
            if ($ttl === null) {
                $this->cacheStore()->forever($cacheKey, $result);
            } else {
                $this->cacheStore()->put($cacheKey, $result, $ttl);
            }
        }

        return $result;
    }

    /**
     * @return array{value: mixed}|null
     */
    protected function fetchValue(string $rootKey): ?array
    {
        $setting = $this->query()->where('key', $rootKey)->first();

        if ($setting === null) {
            return null;
        }

        return ['value' => $this->unwrap($setting)];
    }

    protected function cacheForget(string $rootKey): void
    {
        if (! $this->cacheEnabled()) {
            return;
        }

        $this->cacheStore()->forget($this->cacheKey($rootKey));
    }

    protected function cacheEnabled(): bool
    {
        return (bool) config('polymorphic-settings.cache.enabled', true);
    }

    protected function cacheStore(): CacheRepository
    {
        return Cache::store(config('polymorphic-settings.cache.store'));
    }

    protected function cacheTtl(): ?int
    {
        $ttl = config('polymorphic-settings.cache.ttl');

        return $ttl === null ? null : (int) $ttl;
    }

    protected function cacheKey(string $rootKey): string
    {
        $prefix = (string) config('polymorphic-settings.cache.prefix', 'polymorphic-settings');

        return sprintf('%s:%s:%s', $prefix, $this->scopeKey(), $rootKey);
    }
}
