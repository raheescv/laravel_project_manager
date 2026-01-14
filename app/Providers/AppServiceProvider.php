<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Configuration;
use App\Notifications\DatabaseChannel;
use App\Services\TenantService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register TenantService as singleton to maintain state across the request
        $this->app->singleton(TenantService::class);

        // Bind custom database channel to replace the default one
        $this->app->singleton(BaseDatabaseChannel::class, function ($app) {
            return new DatabaseChannel($app['db']);
        });
    }

    public function boot(): void
    {

        Inertia::setRootView('app-react');

        // Configure Scramble for Bearer Token Authentication
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            });
        // Force HTTPS for assets when the app is served over HTTPS
        if (request()->isSecure() || env('FORCE_HTTPS', false) || env('APP_URL', '')->startsWith('https://')) {
            URL::forceScheme('https');
        }

        if (Schema::hasTable('branches')) {
            Cache::remember('branches', now()->addYear(), function () {
                return Branch::select('id', 'name')->get();
            });
        }
        if (Schema::hasTable('accounts')) {
            Cache::remember('accounts_slug_id_map', now()->addYear(), function () {
                if (Schema::hasColumn('accounts', 'slug')) {
                    return DB::table('accounts')->where('is_locked', 1)->pluck('id', 'slug')->toArray();
                }
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
