<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TenantService
{
    protected ?Tenant $currentTenant = null;

    /**
     * Re-entrancy guard: resolving the authenticated user runs a User query,
     * whose TenantScope calls back into getCurrentTenantId(). Without this
     * flag that inner call would try to resolve the user again, forever.
     */
    protected bool $resolvingFromAuth = false;

    /**
     * Get the current tenant.
     *
     * Tenant identity is REQUEST state. It is resolved from the subdomain (or
     * tenant header) by IdentifyTenant, from the authenticated user, or from
     * an explicit TENANT_ID config for tenantless entry points — never from a
     * cross-request store: a cache key shared by every request would hand one
     * tenant's identity to another tenant's request.
     */
    public function getCurrentTenant(): ?Tenant
    {
        // If tenant is already set in this instance, return it
        if ($this->currentTenant) {
            return $this->currentTenant;
        }

        // Every fallback below needs the framework container (request, auth,
        // config). Bare-container contexts (isolated unit tests) have none —
        // there the only source of truth is setCurrentTenant().
        $app = app();

        // Fallback: Try to get tenant from request attributes (set by middleware)
        if ($app->bound('request')) {
            if (request()->has('tenant')) {
                $tenant = request()->get('tenant');
                if ($tenant instanceof Tenant) {
                    $this->currentTenant = $tenant;

                    return $tenant;
                }
            }

            $tenant = request()->attributes->get('tenant');
            if ($tenant instanceof Tenant) {
                $this->currentTenant = $tenant;

                return $tenant;
            }
        }

        // Fallback: an authenticated user belongs to exactly one tenant.
        if ($app->bound('auth') && ! $this->resolvingFromAuth) {
            $this->resolvingFromAuth = true;
            try {
                $tenantId = Auth::user()?->tenant_id;
            } finally {
                $this->resolvingFromAuth = false;
            }
            if ($tenantId && ($tenant = Tenant::find($tenantId))) {
                $this->currentTenant = $tenant;

                return $tenant;
            }
        }

        // Fallback: explicit TENANT_ID (local development / console commands).
        $configuredId = $app->bound('config') ? config('constants.tenant_id') : null;
        if ($configuredId && ($tenant = Tenant::find($configuredId))) {
            $this->currentTenant = $tenant;

            return $tenant;
        }

        return null;
    }

    /**
     * Set the current tenant (for the lifetime of this request/process only).
     */
    public function setCurrentTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    /**
     * Get the current tenant ID
     */
    public function getCurrentTenantId(): ?int
    {
        return $this->getCurrentTenant()?->id;
    }

    /**
     * Clear the current tenant
     */
    public function clearCurrentTenant(): void
    {
        $this->currentTenant = null;
    }

    /**
     * Find tenant by subdomain
     */
    public function findTenantBySubdomain(string $subdomain): ?Tenant
    {
        return Cache::remember("tenant_subdomain_{$subdomain}", now()->addHours(24), function () use ($subdomain) {
            return Tenant::where('subdomain', $subdomain)
                ->where('is_active', true)
                ->first();
        });
    }
}
