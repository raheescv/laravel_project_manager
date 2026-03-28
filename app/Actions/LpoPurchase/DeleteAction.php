<?php

namespace App\Actions\LpoPurchase;

use App\Models\Purchase;

class DeleteAction
{
    public function execute(array|int $ids)
    {
        try {
            $ids = is_array($ids) ? $ids : [$ids];

            $purchases = Purchase::whereIn('id', $ids)->lpoBased()->get();

            $deletable = $purchases->filter(fn ($p) => $p->status === 'pending');

            $nonDeletable = $purchases->filter(fn ($p) => $p->status !== 'pending');

            Purchase::whereIn('id', $deletable->pluck('id'))->delete();

            $return['success'] = true;

            if ($nonDeletable->isEmpty()) {
                $return['message'] = 'All selected records deleted successfully';
            } else {
                $return['message'] = 'Some records could not be deleted because they are not in pending status';
                $return['data'] = [
                    'deleted_ids' => $deletable->pluck('id'),
                    'skipped_ids' => $nonDeletable->pluck('id'),
                ];
            }
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $return;
    }
}
