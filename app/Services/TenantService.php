<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantService
{
    protected ?Tenant $currentTenant = null;

    /**
     * Get the current tenant
     */
    public function getCurrentTenant(): ?Tenant
    {
        // If tenant is already set in this instance, return it
        if ($this->currentTenant) {
            return $this->currentTenant;
        }

        // Fallback: Try to get tenant from request attributes (set by middleware)
        if (request()->has('tenant')) {
            $tenant = request()->get('tenant');
            if ($tenant instanceof Tenant) {
                $this->currentTenant = $tenant;

                return $tenant;
            }
        }

        // Fallback: Try to get tenant from request attributes
        $tenant = request()->attributes->get('tenant');
        if ($tenant instanceof Tenant) {
            $this->currentTenant = $tenant;

            return $tenant;
        }

        // Fallback: Try to get tenant ID from cache and load it
        $tenantId = Cache::get('current_tenant_id');
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $this->currentTenant = $tenant;

                return $tenant;
            }
        }

        return null;
    }

    /**
     * Set the current tenant
     */
    public function setCurrentTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;

        // Cache the tenant for the request
        Cache::put('current_tenant_id', $tenant->id, now()->addMinutes(5));
    }

    /**
     * Get the current tenant ID
     */
    public function getCurrentTenantId(): ?int
    {
        return $this->currentTenant?->id ?? Cache::get('current_tenant_id');
    }

    /**
     * Clear the current tenant
     */
    public function clearCurrentTenant(): void
    {
        $this->currentTenant = null;
        Cache::forget('current_tenant_id');
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
