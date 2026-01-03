<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Services\TenantService;
use Illuminate\Support\Facades\App;

trait BelongsToTenant
{
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
                $tenantService = App::make(TenantService::class);
                $tenantId = $tenantService->getCurrentTenantId();
                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });
    }
}
