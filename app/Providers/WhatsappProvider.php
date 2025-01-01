<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class WhatsappProvider extends ServiceProvider
{
    public function register()
    {
        App::bind('whatsapp.helper', function () {
            return new \App\Helpers\WhatsappHelper;
        });
    }

    public function boot() {}
}
