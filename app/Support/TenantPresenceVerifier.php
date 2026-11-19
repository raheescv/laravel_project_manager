<?php

namespace App\Support;

use App\Services\TenantService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\DatabasePresenceVerifier;

/**
 * Makes every `exists` and `unique` validation rule tenant-aware.
 *
 * The stock verifier queries the raw table, so `exists:accounts,id` accepted
 * another tenant's account id and `unique:users,email` rejected an email used
 * by a different tenant. When a tenant is resolved and the table carries a
 * tenant_id column, the lookup is narrowed to the current tenant's rows.
 */
class TenantPresenceVerifier extends DatabasePresenceVerifier
{
    /** @var array<string, bool> */
    private static array $tenantColumnCache = [];

    /**
     * @param  string  $table
     * @return Builder
     */
    protected function table($table)
    {
        $query = parent::table($table);

        $tenantId = app(TenantService::class)->getCurrentTenantId();
        if ($tenantId && $this->hasTenantColumn($table)) {
            $query->where($query->from.'.tenant_id', $tenantId);
        }

        return $query;
    }

    private function hasTenantColumn(string $table): bool
    {
        $cacheKey = ($this->connection ?? 'default').'|'.$table;

        return self::$tenantColumnCache[$cacheKey] ??= Schema::connection($this->connection)->hasColumn($table, 'tenant_id');
    }
}
