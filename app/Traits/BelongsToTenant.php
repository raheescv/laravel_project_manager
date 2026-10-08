<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query macros registered by {@see TenantScope::extend()}.
 *
 * @method static Builder<static> withTenant(int|string|null $tenantId)
 * @method static Builder<static> withoutTenant()
 */
trait BelongsToTenant
{
    /**
     * Get the current tenant ID from the service
     */
    protected static function getCurrentTenantId(): ?int
    {
        return app(TenantService::class)->getCurrentTenantId();
    }

    /**
     * Boot the trait
     */
    protected static function bootBelongsToTenant(): void
    {
        // Add global scope
        static::addGlobalScope(new TenantScope());

        // Auto-set tenant_id when creating
        static::creating(function ($model): void {
            if (empty($model->tenant_id)) {
                $tenantId = static::getCurrentTenantId();
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }
}
