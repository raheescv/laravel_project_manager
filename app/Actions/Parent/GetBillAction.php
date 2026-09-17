<?php

namespace App\Actions\Parent;

use App\Models\Sale;
use App\Models\Scopes\AssignedBranchScope;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** One bill for the parent portal: only a completed sale that belongs to the student. */
class GetBillAction
{
    public function execute(int $accountId, int|string $saleId): Sale
    {
        $sale = Sale::withoutGlobalScope(AssignedBranchScope::class)
            ->with(['branch:id,name', 'items.product:id,name', 'items.unit:id,name', 'payments.paymentMethod:id,name'])
            ->where('account_id', $accountId)
            ->where('status', 'completed')
            ->whereKey((int) $saleId)
            ->first();

        if (! $sale) {
            throw new NotFoundHttpException();
        }

        return $sale;
    }
}
