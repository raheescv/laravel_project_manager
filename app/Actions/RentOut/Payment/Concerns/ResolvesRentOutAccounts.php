<?php

namespace App\Actions\RentOut\Payment\Concerns;

use App\Enums\RentOut\AgreementType;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\RentOut;

/**
 * Where a rent-out posting's money side comes from.
 *
 * Every action in this namespace has to answer the same two questions — which
 * locked account carries this tenant's rent/service income, and which account
 * did the user actually pick — so the lookups live here rather than being
 * re-derived (and drifting) in each action.
 */
trait ResolvesRentOutAccounts
{
    /**
     * A tenant's locked account for a slug. Locked accounts are the chart's
     * fixed anchors, so a tenant renaming an account never breaks a posting.
     */
    protected function lockedAccountId(int $tenantId, string $slug): ?int
    {
        return Account::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->where('is_locked', 1)
            ->value('id');
    }

    /**
     * Income account for rent itself. A lease recognises to Sale; a rental
     * recognises to Rent Income when the tenant has one.
     */
    protected function propertyIncomeAccountId(RentOut $rentOut): ?int
    {
        if ($rentOut->agreement_type === AgreementType::Lease) {
            return $this->lockedAccountId($rentOut->tenant_id, 'sale');
        }

        return $this->lockedAccountId($rentOut->tenant_id, 'rent_income')
            ?? $this->lockedAccountId($rentOut->tenant_id, 'sale');
    }

    /**
     * Income account for services and utilities billed alongside the rent.
     */
    protected function serviceIncomeAccountId(RentOut $rentOut): ?int
    {
        return $this->lockedAccountId($rentOut->tenant_id, 'service_charge')
            ?? $this->lockedAccountId($rentOut->tenant_id, 'sale');
    }

    /**
     * Expense account ROI owed to a buyer is booked to. Tenants that existed
     * before ROI get the locked account created the first time it is needed,
     * so an ROI posting never fails on a missing account.
     */
    protected function roiExpenseAccountId(RentOut $rentOut): int
    {
        $existing = $this->lockedAccountId($rentOut->tenant_id, 'roi_expense');
        if ($existing) {
            return (int) $existing;
        }

        $category = AccountCategory::firstOrCreate(['tenant_id' => $rentOut->tenant_id, 'name' => 'Indirect Expense']);

        return (int) Account::forceCreate([
            'tenant_id' => $rentOut->tenant_id,
            'name' => 'ROI Expense',
            'slug' => 'roi_expense',
            'account_type' => 'expense',
            'description' => 'Return on investment owed to buyers on sale agreements',
            'account_category_id' => $category->id,
            'is_locked' => 1,
        ])->id;
    }

    /**
     * Expense account an agent's commission is booked to, created on first use
     * for tenants that existed before commissions.
     */
    protected function commissionExpenseAccountId(RentOut $rentOut): int
    {
        return $this->ensureLockedAccount($rentOut->tenant_id, 'commission_expense', 'Commission Expense', 'expense', 'Indirect Expense', 'Commission earned by agents on closed agreements');
    }

    /**
     * Liability holding approved commission until it is paid to the agent.
     */
    protected function commissionPayableAccountId(RentOut $rentOut): int
    {
        return $this->ensureLockedAccount($rentOut->tenant_id, 'commission_payable', 'Commission Payable', 'liability', 'Current Liabilities', 'Approved agent commission not yet paid');
    }

    protected function ensureLockedAccount(int $tenantId, string $slug, string $name, string $type, string $categoryName, string $description): int
    {
        $existing = $this->lockedAccountId($tenantId, $slug);
        if ($existing) {
            return (int) $existing;
        }

        $category = AccountCategory::firstOrCreate(['tenant_id' => $tenantId, 'name' => $categoryName]);

        return (int) Account::forceCreate([
            'tenant_id' => $tenantId,
            'name' => $name,
            'slug' => $slug,
            'account_type' => $type,
            'description' => $description,
            'account_category_id' => $category->id,
            'is_locked' => 1,
        ])->id;
    }

    /**
     * An account id supplied by the user (the modal's category select), kept
     * only when it really is an account of this tenant.
     */
    protected function tenantAccountId(RentOut $rentOut, mixed $accountId): ?int
    {
        if (! is_numeric($accountId) || (int) $accountId <= 0) {
            return null;
        }

        $id = Account::query()
            ->where('tenant_id', $rentOut->tenant_id)
            ->whereKey((int) $accountId)
            ->value('id');

        return $id ? (int) $id : null;
    }
}
