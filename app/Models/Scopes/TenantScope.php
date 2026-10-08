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
        $scope = $this;

        $builder->macro('withoutTenant', function (Builder $builder) use ($scope) {
            return $builder->withoutGlobalScope($scope);
        });

        $builder->macro('withTenant', function (Builder $builder, $tenantId) use ($scope) {
            return $builder->withoutGlobalScope($scope)->where('tenant_id', $tenantId);
        });
    }
}
