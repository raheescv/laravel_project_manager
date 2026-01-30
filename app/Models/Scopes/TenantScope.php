<?php

namespace App\Models\Scopes;

use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantService = App::make(TenantService::class);
        $tenantId = $tenantService->getCurrentTenantId();

        if ($tenantId) {
            $table = $model->getTable();
            $builder->where("{$table}.tenant_id", $tenantId);
        }
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('withTenant', function (Builder $builder, $tenantId) {
            return $builder->withoutGlobalScope($this)->where('tenant_id', $tenantId);
        });
    }
}
