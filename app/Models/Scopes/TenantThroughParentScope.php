<?php

namespace App\Models\Scopes;

use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\App;

/**
 * Tenant isolation for child tables that carry no tenant_id of their own
 * (sale_payments, product_images, purchase_items, …). Rows are limited to
 * those whose parent — named by the model's tenantParentRelation() — belongs
 * to the current tenant. Like TenantScope, nothing is applied when no tenant
 * is resolved (console, provisioning).
 */
class TenantThroughParentScope implements Scope
{
    /**
     * @var array<class-string<Model>, array{foreignKey: string, ownerKey: string, parent: class-string<Model>}>
     */
    private static array $resolvedParents = [];

    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = App::make(TenantService::class)->getCurrentTenantId();
        if (! $tenantId) {
            return;
        }

        $parentLink = $this->resolveParentLink($model);
        $parent = new $parentLink['parent']();

        $builder->whereIn(
            $model->qualifyColumn($parentLink['foreignKey']),
            $parent->newQueryWithoutScopes()
                ->select($parent->qualifyColumn($parentLink['ownerKey']))
                ->where($parent->qualifyColumn('tenant_id'), $tenantId)
        );
    }

    public function extend(Builder $builder): void
    {
        $scope = $this;

        $builder->macro('withoutTenant', function (Builder $builder) use ($scope) {
            return $builder->withoutGlobalScope($scope);
        });
    }

    /**
     * @return array{foreignKey: string, ownerKey: string, parent: class-string<Model>}
     */
    private function resolveParentLink(Model $model): array
    {
        return self::$resolvedParents[$model::class] ??= (function () use ($model): array {
            /** @var BelongsTo<Model, Model> $relation */
            $relation = $model->{$model->tenantParentRelation()}();

            return [
                'foreignKey' => $relation->getForeignKeyName(),
                'ownerKey' => $relation->getOwnerKeyName(),
                'parent' => $relation->getRelated()::class,
            ];
        })();
    }
}
