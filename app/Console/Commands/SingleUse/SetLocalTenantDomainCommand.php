<?php

namespace App\Console\Commands\SingleUse;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetLocalTenantDomainCommand extends Command
{
    protected $signature = 'tenant:set-local-domain
        {--domain=project_manager.test : The local development domain to point every tenant at}';

    protected $description = 'Point every tenant at the local dev domain (project_manager.test). Refused on production.';

    public function handle()
    {
        // This rewrites the tenant table to local dev values and would break routing on a
        // live server, so it is a local-only tool. Abort hard on production.
        if (app()->environment('production')) {
            $this->error('Refusing to run on production: this command rewrites tenant domains for local development only.');

            return self::FAILURE;
        }

        $domain = $this->option('domain');
        $subdomain = explode('.', $domain)[0];

        $updated = Tenant::query()->update([
            'subdomain' => $subdomain,
            'domain' => $domain,
        ]);

        $this->info("Pointed {$updated} tenant(s) at {$domain} (subdomain: {$subdomain}).");

        Artisan::call('db:ensure-procedures');

        return self::SUCCESS;
    }
}
