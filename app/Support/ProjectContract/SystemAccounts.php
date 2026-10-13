<?php

namespace App\Support\ProjectContract;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Services\TenantService;
use App\Support\TenantCache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The locked system accounts project-contract security deposits post to.
 *
 * AccountSeeder creates them for new tenants; a tenant that existed before
 * this module gets each one created the first time it is needed, so a
 * security posting never fails on a missing account.
 */
final class SystemAccounts
{
    public const VENDOR_SECURITY_DEPOSIT = 'vendor_security_deposit';

    public const SECURITY_DEPOSIT_PAID = 'security_deposit_paid';

    public const FORFEITED_DEPOSITS = 'forfeited_deposits';

    public const SECURITY_DEPOSIT_LOSS = 'security_deposit_loss';

    /**
     * @return array<string, array{name: string, account_type: string, category: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            self::VENDOR_SECURITY_DEPOSIT => ['name' => 'Vendor Security Deposit', 'account_type' => 'liability', 'category' => 'Current Liabilities', 'description' => 'Refundable security deposits received from vendors on project contracts'],
            self::SECURITY_DEPOSIT_PAID => ['name' => 'Security Deposit Paid', 'account_type' => 'asset', 'category' => 'Current Asset', 'description' => 'Refundable security deposits paid to vendors on project contracts'],
            self::FORFEITED_DEPOSITS => ['name' => 'Forfeited Deposits', 'account_type' => 'income', 'category' => 'Indirect Income', 'description' => 'Vendor security deposits and guarantees kept after a breach'],
            self::SECURITY_DEPOSIT_LOSS => ['name' => 'Security Deposit Loss', 'account_type' => 'expense', 'category' => 'Indirect Expense', 'description' => 'Security deposits paid to vendors that were not refunded'],
        ];
    }

    /** The current tenant's account id for one of the slugs above, created on first use. */
    public static function id(string $slug): int
    {
        $existing = Account::slugIdMap()[$slug] ?? null;
        if ($existing) {
            return (int) $existing;
        }

        $definition = self::definitions()[$slug] ?? throw new RuntimeException("Unknown project contract account '{$slug}'.");
        $tenantId = app(TenantService::class)->getCurrentTenantId();
        $category = AccountCategory::firstOrCreate(['tenant_id' => $tenantId, 'name' => $definition['category']]);

        $id = DB::table('accounts')->where('tenant_id', $tenantId)->where('slug', $slug)->value('id')
            ?? DB::table('accounts')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => $definition['name'],
                'slug' => $slug,
                'account_type' => $definition['account_type'],
                'description' => $definition['description'],
                'account_category_id' => $category->id,
                'is_locked' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        TenantCache::forget('accounts_slug_id_map');

        return (int) $id;
    }
}
