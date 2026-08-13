<?php

namespace App\Actions\RentOut\Payment\Concerns;

use App\Enums\RentOut\AgreementType;
use App\Models\Account;
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
