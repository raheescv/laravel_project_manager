<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class OllamaProvider extends ServiceProvider
{
    public function register()
    {
        App::bind('ollama.helper', function () {
            return new \App\Helpers\OllamaHelper();
        });
    }

    public function boot() {}
}
