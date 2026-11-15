<?php

namespace App\Actions\Student\PreOrder;

use App\Actions\Sale\UpdateAction as SaleUpdateAction;
use App\Models\Sale;
use App\Models\StudentPreOrderCollection;
use App\Models\User;
use App\Support\Student\StudentSettings;
use Exception;

/**
 * Give back a paid pre-order day before its cut-off: the sale is cancelled, which
 * reverses its stock and its journals and so puts the money back on the card, and
 * the collection row goes, so the day counts as unpaid again. The cancelled sale
 * stays in the sales list as the record. The caller owns the transaction.
 */
class RefundAction
{
    public function execute(StudentPreOrderCollection $collection, ?int $userId = null): array
    {
        try {
            $sale = $collection->sale_id ? Sale::withoutGlobalScopes()->where('tenant_id', $collection->tenant_id)->find($collection->sale_id) : null;

            if ($sale && $sale->status === 'completed') {
                $settings = StudentSettings::current();
                $recorder = $userId ?? $settings->preOrderUserId ?? User::systemUserId((int) $collection->tenant_id) ?? $sale->created_by;
                $response = (new SaleUpdateAction())->execute(['status' => 'cancelled'], $sale->id, $recorder);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $collection->delete();

            $return['success'] = true;
            $return['message'] = 'Pre-order refunded to the card';
            $return['data'] = $sale;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
