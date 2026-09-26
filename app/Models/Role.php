<?php

namespace App\Models;

use App\Services\TenantService;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Role names repeat across tenants ("Admin"), so a lookup by name —
 * assignRole('Admin'), hasRole('Admin') — must stay inside the current tenant,
 * for the same reason as App\Models\Permission.
 */
class Role extends SpatieRole
{
    /**
     * @param  array<string, mixed>  $params
     */
    protected static function findByParam(array $params = []): ?RoleContract
    {
        $tenantId = app(TenantService::class)->getCurrentTenantId();
        if ($tenantId && ! array_key_exists('tenant_id', $params)) {
            $params['tenant_id'] = $tenantId;
        }

        return parent::findByParam($params);
    }
}
