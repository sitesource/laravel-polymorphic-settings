<?php

namespace SiteSource\PolymorphicSettings\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

class InstallCommand extends Command
{
    public $signature = 'polymorphic-settings:install
                         {--force : Overwrite existing config and migration files}';

    public $description = 'Install and configure polymorphic settings';

    public function handle(): int
    {
        note('Installing sitesource/laravel-polymorphic-settings');

        $keyType = $this->chooseKeyType();

        if ($keyType === 'uuid') {
            $this->writeEnvVar('POLYMORPHIC_SETTINGS_KEY_TYPE', 'uuid');
        }

        if ($this->askConfirm('Publish the config file?', default: true)) {
            $this->call('vendor:publish', [
                '--tag' => 'polymorphic-settings-config',
                '--force' => $this->option('force'),
            ]);
        }

        $runMigrations = $this->askConfirm('Publish and run the migration now?', default: true);

        if ($runMigrations) {
            $this->call('vendor:publish', [
                '--tag' => 'polymorphic-settings-migrations',
                '--force' => $this->option('force'),
            ]);
            $this->call('migrate');
        } else {
            note('Skipped migration. Publish and run manually when ready:');
            note('  php artisan vendor:publish --tag=polymorphic-settings-migrations');
            note('  php artisan migrate');
        }

        info('Done. See the README for usage.');

        return self::SUCCESS;
    }

    protected function chooseKeyType(): string
    {
        $detected = $this->detectKeyType();

        info(sprintf(
            'Detected primary key type on %s: %s',
            $this->userModelName(),
            $detected,
        ));

        if (! $this->input->isInteractive()) {
            return $detected;
        }

        return select(
            label: 'What primary key type should the polymorphic_settings table use?',
            options: [
                'int' => 'int (auto-incrementing integer — standard Laravel default)',
                'uuid' => 'uuid (string, for apps that use UUID primary keys)',
            ],
            default: $detected,
            hint: 'Pick the type that matches your app. You can change this later by editing the env var or the config file before re-running migrations.',
        );
    }

    protected function askConfirm(string $label, bool $default): bool
    {
        if (! $this->input->isInteractive()) {
            return $default;
        }

        return confirm($label, default: $default);
    }

    protected function detectKeyType(): string
    {
        $userModel = $this->userModelName();

        if (! class_exists($userModel)) {
            return 'int';
        }

        try {
            /** @var Model $instance */
            $instance = new $userModel;

            return $instance->getKeyType() === 'string' ? 'uuid' : 'int';
        } catch (\Throwable) {
            return 'int';
        }
    }

    protected function userModelName(): string
    {
        return (string) config('auth.providers.users.model', 'App\\Models\\User');
    }

    protected function writeEnvVar(string $key, string $value): void
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            warning(".env not found — add manually: {$key}={$value}");

            return;
        }

        $contents = (string) file_get_contents($path);
        $line = "{$key}={$value}";
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents) === 1) {
            $updated = (string) preg_replace($pattern, $line, $contents);
        } else {
            $updated = rtrim($contents, "\n")."\n\n{$line}\n";
        }

        file_put_contents($path, $updated);

        info("Wrote {$key}={$value} to .env");
    }
}
