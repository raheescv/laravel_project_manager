<?php

namespace App\Actions\PurchaseRequest;

use App\Enums\PurchaseRequest\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function execute(array|int $ids)
    {
        try {
            $ids = is_array($ids) ? $ids : [$ids];

            $return = [];
            DB::transaction(function () use ($ids,  &$return) {
                PurchaseRequest::whereIn('id', $ids)->where('status', PurchaseRequestStatus::PENDING)->delete();

                $return['success'] = true;
                $return['message'] = __('Successfully deleted purchase requests');
            });
        } catch (\Throwable $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }
        return $return;
    }
}
