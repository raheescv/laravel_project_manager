<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (Schema::hasTable('branches')) {
            Cache::remember('branches', now()->addYear(), function () {
                info('branches remember');

                return Branch::select('id', 'name')->get();
            });
        }
        if (Schema::hasTable('configurations')) {
            Cache::remember('barcode_type', now()->addYear(), function () {
                info('configuration remember');

                return Configuration::where('key', 'barcode_type')->value('value');
            });
            Cache::remember('payment_methods', now()->addYear(), function () {
                info('payment_methods remember');
                $list = Configuration::where('key', 'payment_methods')->value('value');

                return json_decode($list, 1);
            });
            Cache::remember('sale_type', now()->addYear(), function () {
                info('sale_type remember');

                return Configuration::where('key', 'sale_type')->value('value');
            });

            Cache::remember('theme_settings', now()->addYear(), function () {
                info('theme_settings remember');
                $themeSettings = Configuration::where('key', 'theme_settings')->value('value');

                return $themeSettings ? json_decode($themeSettings, true) : null;
            });
            Cache::remember('logo', now()->addYear(), function () {
                info('logo remember');

                return Configuration::where('key', 'logo')->value('value') ?? asset('assets/img/logo.svg');
            });
            Cache::remember('mobile', now()->addYear(), function () {
                info('mobile remember');

                return Configuration::where('key', 'mobile')->value('value');
            });
        }
        // Gate::after(function ($user, $ability) {
        //     return $user->hasRole('Super Admin') || $user->hasPermissionTo($ability);
        // });
    }
}
