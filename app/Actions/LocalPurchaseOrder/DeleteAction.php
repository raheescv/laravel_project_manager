<?php

namespace App\Actions\LocalPurchaseOrder;

use App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus;
use App\Models\LocalPurchaseOrder;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function execute(array|int $ids)
    {
        try {
            $ids = is_array($ids) ? $ids : [$ids];

            $return = [];
            DB::transaction(function () use ($ids, &$return) {
                LocalPurchaseOrder::whereIn('id', $ids)->where('status', LocalPurchaseOrderStatus::PENDING)->delete();

                $return['success'] = true;
                $return['message'] = __('Successfully deleted local purchase orders');
            });
        } catch (\Throwable $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
