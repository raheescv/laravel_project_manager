<?php

namespace App\Actions\PurchaseRequest;

use App\Enums\PurchaseRequest\PurchaseRequestStatus;
use App\Models\PurchaseRequest;

class DeleteAction
{
    public function execute(array|int $ids)
    {
        try {
            $ids = is_array($ids) ? $ids : [$ids];

            $return = [];
            $requests = PurchaseRequest::whereIn('id', $ids)->get();

            $deletable = $requests->filter(fn ($r) => $r->status === PurchaseRequestStatus::PENDING);

            $nonDeletable = $requests->filter(fn ($r) => $r->status !== PurchaseRequestStatus::PENDING);
            PurchaseRequest::whereIn('id', $deletable->pluck('id'))->delete();

            if ($nonDeletable->isEmpty()) {
                $return['message'] = 'All selected records deleted successfully';
            } else {
                $return['message'] = 'Some records could not be deleted because they are not in pending status';
            }

            $data = [
                'deleted_ids' => $deletable->pluck('id'),
                'skipped_ids' => $nonDeletable->pluck('id'),
            ];
            $return['success'] = true;
            $return['data'] = $data;
        } catch (\Throwable $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
