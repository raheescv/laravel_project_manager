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

                $orders = LocalPurchaseOrder::whereIn('id', $ids)->get();

                $deletable = $orders->filter(
                    fn ($o) => $o->status === LocalPurchaseOrderStatus::PENDING
                );

                $nonDeletable = $orders->filter(
                    fn ($o) => $o->status !== LocalPurchaseOrderStatus::PENDING
                );

                LocalPurchaseOrder::whereIn('id', $deletable->pluck('id'))->delete();

                $return['success'] = true;

                if ($nonDeletable->isEmpty()) {
                    $return['message'] = 'All selected records deleted successfully';
                } else {
                    $return['message'] =
                        'Some records could not be deleted because they are not in pending status';

                    $return['data'] = [
                        'deleted_ids' => $deletable->pluck('id'),
                        'skipped_ids' => $nonDeletable->pluck('id'),
                    ];
                }
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $return;
    }
}
