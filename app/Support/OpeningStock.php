<?php

namespace App\Support;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;

/**
 * The locked equity account opening stock is purchased "from".
 *
 * Imported opening stock becomes a draft purchase against this account, so completing it
 * posts Dr Inventory / Cr Opening Stock instead of a payable to a made-up vendor.
 * AccountSeeder creates it for new tenants; older tenants get it on first use.
 */
final class OpeningStock
{
    public const SLUG = 'opening_stock';

    /** The current tenant's Opening Stock account id, created on first use. */
    public static function accountId(): int
    {
        $existing = self::existingAccountId();
        if ($existing) {
            return $existing;
        }

        $tenantId = app(TenantService::class)->getCurrentTenantId();
        $category = AccountCategory::firstOrCreate(['tenant_id' => $tenantId, 'name' => 'Equity']);

        $id = DB::table('accounts')->where('tenant_id', $tenantId)->where('slug', self::SLUG)->value('id')
            ?? DB::table('accounts')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => 'Opening Stock',
                'slug' => self::SLUG,
                'account_type' => 'equity',
                'description' => 'Value of stock on hand when the books were opened',
                'account_category_id' => $category->id,
                'is_locked' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        TenantCache::forget('accounts_slug_id_map');

        return (int) $id;
    }

    /** The account id when the tenant already has one; never creates it. */
    public static function existingAccountId(): ?int
    {
        $id = Account::slugIdMap()[self::SLUG] ?? null;

        return $id ? (int) $id : null;
    }
}
