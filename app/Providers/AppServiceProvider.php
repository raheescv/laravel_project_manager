<?php

namespace App\Providers;

use App\Ai\Providers\FixedOpenAiProvider;
use App\Notifications\DatabaseChannel;
use App\Services\TenantService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Ai\Ai;
use Laravel\Ai\Gateway\Prism\PrismGateway;
use Opcodes\LogViewer\Facades\LogViewer;

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
        if (request()->isSecure() || config('constants.force_https', false) || str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        // Tenant-scoped settings and lookup maps (branches, accounts_slug_id_map,
        // logo, theme_settings, payment_methods, …) are NOT warmed here: boot()
        // runs before the tenant is resolved, so any value computed here would
        // belong to whichever tenant happened to warm it first and would then be
        // served to every other tenant. They are resolved lazily, keyed by the
        // current tenant, via tenant_cache() / App\Support\TenantCache.

        // Gate::after(function ($user, $ability) {
        //     return $user->hasRole('Super Admin') || $user->hasPermissionTo($ability);
        // });

        LogViewer::auth(function (Request $request): bool {
            return $request->user()?->can('log.log viewer') ?? false;
        });

        $this->registerFixedAiProvider();
    }

    /**
     * Register the fixed OpenAI provider that removes the 'moderation' parameter
     * which causes 400 errors in the current version of the AI SDK.
     */
    protected function registerFixedAiProvider(): void
    {
        try {
            if (class_exists(Ai::class)) {
                Ai::extend('openai', function ($app, array $config) {
                    return new FixedOpenAiProvider(
                        new PrismGateway($app['events']),
                        $config,
                        $app->make(Dispatcher::class)
                    );
                });
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
