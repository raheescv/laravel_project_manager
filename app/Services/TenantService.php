<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        try {
            $tenantId = Cache::get('current_tenant_id');
            if ($tenantId) {
                /** @var Tenant|null $tenant */
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    $this->currentTenant = $tenant;

                    return $tenant;
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Unable to read current tenant from cache.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Set the current tenant
     */
    public function setCurrentTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;

        try {
            Cache::put('current_tenant_id', $tenant->id, now()->addMinutes(5));
        } catch (Throwable $exception) {
            Log::warning('Unable to cache current tenant.', [
                'tenant_id' => $tenant->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Get the current tenant ID
     */
    public function getCurrentTenantId(): ?int
    {
        if ($this->currentTenant) {
            return $this->currentTenant->id;
        }

        try {
            return Cache::get('current_tenant_id');
        } catch (Throwable $exception) {
            Log::warning('Unable to read current tenant id from cache.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Clear the current tenant
     */
    public function clearCurrentTenant(): void
    {
        $this->currentTenant = null;
        try {
            Cache::forget('current_tenant_id');
        } catch (Throwable $exception) {
            Log::warning('Unable to clear current tenant from cache.', [
                'message' => $exception->getMessage(),
            ]);
        }
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
