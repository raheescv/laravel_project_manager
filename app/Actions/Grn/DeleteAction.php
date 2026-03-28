<?php

namespace App\Actions\Grn;

use App\Enums\Grn\GrnStatus;
use App\Models\Grn;

class DeleteAction
{
    public function execute(array|int $ids)
    {
        try {
            $ids = is_array($ids) ? $ids : [$ids];

            $return = [];

            $grns = Grn::whereIn('id', $ids)->get();

            $deletable = $grns->filter(
                fn ($g) => $g->status === GrnStatus::PENDING
            );

            $nonDeletable = $grns->filter(
                fn ($g) => $g->status !== GrnStatus::PENDING
            );

            Grn::whereIn('id', $deletable->pluck('id'))->delete();

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
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $return;
    }
}
