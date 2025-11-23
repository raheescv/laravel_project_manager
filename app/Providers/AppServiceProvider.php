<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Configuration;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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

        // Configure Scramble for Bearer Token Authentication
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });
        // Force HTTPS for assets when the app is served over HTTPS
        if (request()->isSecure() || env('FORCE_HTTPS', false) || str_starts_with(env('APP_URL', ''), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if (Schema::hasTable('branches')) {
            Cache::remember('branches', now()->addYear(), function () {
                return Branch::select('id', 'name')->get();
            });
        }
        if (Schema::hasTable('configurations')) {
            Cache::remember('barcode_type', now()->addYear(), function () {
                return Configuration::where('key', 'barcode_type')->value('value');
            });
            Cache::remember('payment_methods', now()->addYear(), function () {
                $list = Configuration::where('key', 'payment_methods')->value('value');

                return json_decode($list, 1);
            });
            Cache::remember('sale_type', now()->addYear(), function () {
                return Configuration::where('key', 'sale_type')->value('value');
            });

            Cache::remember('theme_settings', now()->addYear(), function () {
                $themeSettings = Configuration::where('key', 'theme_settings')->value('value');

                return $themeSettings ? json_decode($themeSettings, true) : null;
            });
            Cache::remember('logo', now()->addYear(), function () {
                return Configuration::where('key', 'logo')->value('value') ?? asset('assets/img/logo.svg');
            });
            Cache::remember('mobile', now()->addYear(), function () {
                return Configuration::where('key', 'mobile')->value('value');
            });
            Cache::remember('country_id', now()->addYear(), function () {
                return Configuration::where('key', 'country_id')->value('value');
            });
        }
        // Gate::after(function ($user, $ability) {
        //     return $user->hasRole('Super Admin') || $user->hasPermissionTo($ability);
        // });
    }
}
