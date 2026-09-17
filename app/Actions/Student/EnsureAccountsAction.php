<?php

namespace App\Actions\Student;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Services\TenantService;
use App\Support\TenantCache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Provision what student cards need in the current tenant's chart of accounts.
 *
 * - The "Student Card Balances" category (under Current Liabilities when the
 *   tenant has it) that every student account sits in, so prepaid balances read
 *   as money owed to families on the balance sheet.
 * - The locked `student_card` payment method ("Student Wallet") a card sale is paid with. It never
 *   carries a balance: the sale journal skips its leg because the sale's own
 *   debit to the student account already spends the balance.
 *
 * Created on first use rather than for every tenant, so shops that never enrol a
 * student do not get school accounts in their chart. Idempotent.
 */
class EnsureAccountsAction
{
    public const CATEGORY_NAME = 'Student Card Balances';

    public const CARD_SLUG = 'student_card';

    /** @return array{category_id: int, card_method_id: int} */
    public function execute(): array
    {
        $tenantId = app(TenantService::class)->getCurrentTenantId();
        if (! $tenantId) {
            throw new RuntimeException('No tenant resolved; refusing to provision student accounts.');
        }

        $parentId = AccountCategory::where('name', 'Current Liabilities')->value('id');
        $category = AccountCategory::firstOrCreate(['name' => self::CATEGORY_NAME], ['parent_id' => $parentId]);

        $cardMethodId = Account::slugIdMap()[self::CARD_SLUG] ?? null;

        if (! $cardMethodId) {
            // Through the query builder because slug and is_locked are deliberately
            // not fillable (the same reason AccountSeeder writes them this way).
            $cardMethodId = DB::table('accounts')
                ->where('tenant_id', $tenantId)
                ->where('slug', self::CARD_SLUG)
                ->whereNull('deleted_at')
                ->value('id');

            if (! $cardMethodId) {
                $cardMethodId = DB::table('accounts')->insertGetId([
                    'tenant_id' => $tenantId,
                    'account_type' => 'liability',
                    'account_category_id' => $category->id,
                    // Deliberately not "Student Card": reports group payment methods by
                    // name (anything containing "card" is counted as bank-card takings, see
                    // BuildDaySessionReportAction / MonthlySaleReport), and this must fall in neither bucket.
                    'name' => 'Student Wallet',
                    'slug' => self::CARD_SLUG,
                    'description' => 'Purchases paid from a student\'s prepaid card balance. Carries no balance of its own.',
                    'is_locked' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            TenantCache::forget('accounts_slug_id_map');
        }

        return ['category_id' => (int) $category->id, 'card_method_id' => (int) $cardMethodId];
    }

    /** The Student Card payment method id, or null when the tenant has never enrolled a student. */
    public static function cardMethodId(): ?int
    {
        $id = Account::slugIdMap()[self::CARD_SLUG] ?? null;

        return $id ? (int) $id : null;
    }
}
