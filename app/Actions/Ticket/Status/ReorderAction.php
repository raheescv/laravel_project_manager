<?php

namespace App\Actions\Ticket\Status;

use App\Models\TicketStatus;
use Exception;
use Illuminate\Support\Facades\DB;

class ReorderAction
{
    /**
     * Put the columns in the given order; every column must be listed once.
     *
     * @param  list<int>  $ids
     * @return array{success: bool, message: string}
     */
    public function execute(array $ids): array
    {
        try {
            DB::transaction(function () use ($ids): void {
                $statuses = TicketStatus::ordered()->keyBy('id');
                $ids = array_map('intval', $ids);
                if (count($ids) !== $statuses->count() || array_diff($ids, $statuses->keys()->all())) {
                    throw new Exception('The board changed in the meantime — reload and try again.', 1);
                }

                foreach ($ids as $position => $id) {
                    if ($statuses[$id]->sort_order !== $position) {
                        $statuses[$id]->update(['sort_order' => $position]);
                    }
                }
            });

            $return['success'] = true;
            $return['message'] = 'Column order saved.';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
