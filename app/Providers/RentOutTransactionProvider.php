<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class RentOutTransactionProvider extends ServiceProvider
{
    public function register()
    {
        App::bind('rent_out_transaction.helper', function () {
            return new \App\Helpers\RentOutTransactionHelper();
        });
    }

    public function boot() {}
}
