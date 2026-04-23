<?php

namespace SiteSource\PolymorphicSettings;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
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
        [$rootKey, $nestedPath] = $this->splitKey($key);

        $setting = $this->query()->where('key', $rootKey)->first();

        if ($setting === null) {
            return $default;
        }

        $value = $setting->value;

        if ($nestedPath === null) {
            return $value;
        }

        if (! is_array($value)) {
            return $default;
        }

        return Arr::get($value, $nestedPath, $default);
    }

    public function put(string $key, mixed $value): self
    {
        $this->query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'configurable_type' => $this->configurableType,
                'configurable_id' => $this->configurableId,
            ],
        );

        return $this;
    }

    public function putMany(array $values): self
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value);
        }

        return $this;
    }

    public function forget(string $key): bool
    {
        return $this->query()->where('key', $key)->delete() > 0;
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
            ->pluck('value', 'key')
            ->all();
    }

    public function getMany(array $keys): array
    {
        $found = $this->query()
            ->whereIn('key', $keys)
            ->get()
            ->pluck('value', 'key');

        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $found->get($key);
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
        $existing = $this->get($key);
        $merged = is_array($existing) ? array_merge($existing, $values) : $values;

        return $this->put($key, $merged);
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
        $scopeKey = $this->isGlobal()
            ? Setting::GLOBAL_SCOPE_KEY
            : $this->configurableType.':'.$this->configurableId;

        return Setting::query()->where('scope_key', $scopeKey);
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
}
