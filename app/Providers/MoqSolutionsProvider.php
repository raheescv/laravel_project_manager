<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class MoqSolutionsProvider extends ServiceProvider
{
    public function register()
    {
        App::bind('moq.helper', function () {
            return new \App\Helpers\MoqSolutionsHelper();
        });
    }

    public function boot() {}
}
