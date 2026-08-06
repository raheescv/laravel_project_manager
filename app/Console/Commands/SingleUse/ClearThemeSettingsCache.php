<?php

namespace App\Console\Commands\SingleUse;

use App\Support\TenantCache;
use Illuminate\Console\Command;

class ClearThemeSettingsCache extends Command
{
    protected $signature = 'theme:clear-cache';

    protected $description = 'Clear the theme settings cache';

    public function handle()
    {
        TenantCache::forget('theme_settings');
        $this->info('Theme settings cache cleared successfully');
    }
}
