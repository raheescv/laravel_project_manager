<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class SaleProvider extends ServiceProvider
{
    public function register()
    {
        App::bind('sale.helper', function () {
            return new \App\Helpers\SaleHelper();
        });
    }

    public function boot() {}
}
