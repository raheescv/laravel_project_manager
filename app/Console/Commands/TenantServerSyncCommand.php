<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantServerService;
use Illuminate\Console\Command;

/**
 * Applies Tenant Control's server side. Runs as ROOT, every minute, from the
 * cron file it installs itself with --shared:
 *
 *   sudo php artisan tenant:server-sync --shared   (once, and after moving the app)
 *
 * Each run writes/removes the nginx site of every tenant whose custom domain
 * is pending, issues its Let's Encrypt certificate, and removes orphaned
 * sites. Nothing happens when no tenant is pending, so the cron is cheap.
 */
class TenantServerSyncCommand extends Command
{
    protected $signature = 'tenant:server-sync
        {--shared : Also install the queue worker (Supervisor) and the cron file}
        {--retry : Retry tenants whose last sync failed}
        {--dry-run : Print what would be written without touching the server}';

    protected $description = 'Write nginx sites and certificates for tenant custom domains; with --shared, install Supervisor and cron';

    public function handle(TenantServerService $server): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && config('tenant_server.require_root') && function_exists('posix_geteuid') && posix_geteuid() !== 0) {
            $this->error('Run this as root (sudo): it writes nginx, Supervisor and cron configuration.');

            return self::FAILURE;
        }

        // Running as root: anything written under storage/ now would be
        // root-owned and lock the web user out of its own logs.
        config(['logging.default' => 'stderr']);

        if ($this->option('shared')) {
            if ($dryRun) {
                $this->line('<comment>'.$server->supervisorPath().'</comment>');
                $this->line($server->renderSupervisor());
                $this->line('<comment>'.config('tenant_server.cron_file').'</comment>');
                $this->line($server->renderCron());
            } else {
                foreach ($server->installShared() as $line) {
                    $this->info($line);
                }
            }
        }

        $statuses = $this->option('retry') ? [Tenant::DOMAIN_PENDING, Tenant::DOMAIN_FAILED] : [Tenant::DOMAIN_PENDING];
        $tenants = Tenant::withTrashed()->whereIn('domain_status', $statuses)->orderBy('id')->get();

        foreach ($tenants as $tenant) {
            $wanted = ! $tenant->trashed() && $tenant->is_active && $tenant->hasCustomDomain();

            if ($dryRun) {
                $this->line("<comment>#{$tenant->id} {$tenant->name}</comment> → ".($wanted ? "write {$server->sitePath($tenant->id)}" : 'remove site'));
                if ($wanted) {
                    $this->line($server->renderSite($tenant, $server->certificateExists($tenant->domain)));
                }

                continue;
            }

            if (! $wanted) {
                $server->removeSite($tenant->id);
                $this->record($tenant, ['status' => null, 'error' => null]);
                $this->info("#{$tenant->id} {$tenant->name}: site removed");

                continue;
            }

            $result = $server->syncTenant($tenant);
            $this->record($tenant, $result);
            $result['status'] === Tenant::DOMAIN_ACTIVE
                ? $this->info("#{$tenant->id} {$tenant->domain}: active")
                : $this->warn("#{$tenant->id} {$tenant->domain}: {$result['error']}");
        }

        if (! $dryRun) {
            $this->removeOrphans($server);
        }

        return self::SUCCESS;
    }

    /**
     * Written with a plain query: the model's saving hook would flip the status
     * straight back to pending.
     *
     * @param  array{status: ?string, error: ?string}  $result
     */
    private function record(Tenant $tenant, array $result): void
    {
        Tenant::withTrashed()->whereKey($tenant->id)->update([
            'domain_status' => $result['status'],
            'domain_error' => $result['error'],
            'domain_synced_at' => now(),
        ]);
    }

    /**
     * Sites on disk for tenants that no longer exist, or no longer want one
     * and are not waiting to be processed.
     */
    private function removeOrphans(TenantServerService $server): void
    {
        $siteIds = $server->managedSiteIds();
        if (! $siteIds) {
            return;
        }

        $keep = Tenant::whereIn('id', $siteIds)->where('is_active', true)->get()
            ->filter(fn (Tenant $tenant): bool => $tenant->hasCustomDomain())
            ->pluck('id')
            ->all();

        foreach (array_diff($siteIds, $keep) as $tenantId) {
            $server->removeSite($tenantId);
            $this->info("#{$tenantId}: orphaned site removed");
        }
    }
}
