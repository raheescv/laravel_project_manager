<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearThemeSettingsCache extends Command
{
    protected $signature = 'theme:clear-cache';

    protected $description = 'Clear the theme settings cache';

    public function handle()
    {
        Cache::forget('theme_settings');
        $this->info('Theme settings cache cleared successfully');
    }
}
