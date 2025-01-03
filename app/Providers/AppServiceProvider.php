<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Gate::after(function ($user, $ability) {
        //     return $user->hasRole('Super Admin') || $user->hasPermissionTo($ability);
        // });
    }
}
