<?php

namespace App\Providers;

use App\Helpers\TelegramHelper;
use App\Models\Configuration;
use Illuminate\Support\ServiceProvider;
use Telegram\Bot\Api;

class TelegramServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Api::class, function ($app) {
            return new Api(Configuration::where('key', 'telegram_bot_token')->value('value') ?? null);
        });

        $this->app->singleton(TelegramHelper::class, function ($app) {
            return new TelegramHelper($app->make(Api::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
