<?php

namespace App\Models;

use App\Services\TenantService;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Every tenant has its own copy of each permission row (same name, its own
 * tenant_id), and Spatie resolves `can('sale.view')` by name alone — the first
 * matching row wins, which is tenant 1's. A role in any other tenant holds its
 * own tenant's rows, so every shared name was denied. Lookups are therefore
 * narrowed to the current tenant whenever one is known.
 */
class Permission extends SpatiePermission
{
    /**
     * @param  array<string, mixed>  $params
     */
    protected static function getPermissions(array $params = [], bool $onlyOne = false): Collection
    {
        $tenantId = app(TenantService::class)->getCurrentTenantId();
        if ($tenantId && ! array_key_exists('tenant_id', $params)) {
            $params['tenant_id'] = $tenantId;
        }

        return parent::getPermissions($params, $onlyOne);
    }
}
