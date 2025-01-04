<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
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
        Cache::remember('branches', now()->addYear(), function () {
            info('branches remember');

            return Branch::select('id', 'name')->get();
        });
        Cache::remember('barcode_type', now()->addYear(), function () {
            info('configuration remember');

            return Configuration::firstWhere('key', 'barcode_type')?->value('value');
        });
        // Gate::after(function ($user, $ability) {
        //     return $user->hasRole('Super Admin') || $user->hasPermissionTo($ability);
        // });
    }
}
