<?php

namespace App\Jobs\Middleware;

use App\Models\Tenant;
use App\Services\TenantService;
use Closure;

/**
 * A queued job has no request or session, so TenantService cannot resolve a
 * tenant and every BelongsToTenant query runs unscoped. This runs the job
 * inside the tenant it was dispatched for, then restores the previous one so
 * the worker (or the request, on the sync driver) is left as it was.
 */
class RunInTenant
{
    public function __construct(public ?int $tenantId) {}

    /**
     * Process the queued job.
     *
     * @param  \Closure(object): void  $next
     */
    public function handle(object $job, Closure $next): void
    {
        $tenantService = app(TenantService::class);
        $previousTenant = $tenantService->getCurrentTenant();
        $tenant = $this->tenantId ? Tenant::find($this->tenantId) : null;

        if (! $tenant) {
            $next($job);

            return;
        }

        $tenantService->setCurrentTenant($tenant);

        try {
            $next($job);
        } finally {
            if ($previousTenant) {
                $tenantService->setCurrentTenant($previousTenant);
            } else {
                $tenantService->clearCurrentTenant();
            }
        }
    }
}
