<?php

namespace App\Traits;

use App\Models\Scopes\TenantThroughParentScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * For child models whose table has no tenant_id: scopes every query to rows
 * whose parent (a BelongsTo to a tenant-owned model) is in the current tenant.
 * The using model must define tenantParentRelation() returning that relation's name.
 *
 * @method static Builder<static> withoutTenant()
 */
trait BelongsToTenantThroughParent
{
    abstract public function tenantParentRelation(): string;

    protected static function bootBelongsToTenantThroughParent(): void
    {
        static::addGlobalScope(new TenantThroughParentScope());
    }
}
