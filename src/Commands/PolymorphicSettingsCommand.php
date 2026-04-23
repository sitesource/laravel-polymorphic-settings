<?php

namespace SiteSource\PolymorphicSettings\Commands;

use Illuminate\Console\Command;

class PolymorphicSettingsCommand extends Command
{
    public $signature = 'polymorphic-settings';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
