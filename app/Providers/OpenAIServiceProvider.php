<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenAI\Laravel\ServiceProvider as OpenAIProvider;

class OpenAIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(OpenAIProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
