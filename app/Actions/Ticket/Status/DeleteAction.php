<?php

namespace App\Actions\Ticket\Status;

use App\Models\Ticket;
use App\Models\TicketStatus;
use Exception;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    /**
     * Remove a column. Its tickets move to `$moveTo` first; a board always
     * keeps at least one column.
     *
     * @return array{success: bool, message: string, data?: array{moved: int}}
     */
    public function execute(int $id, ?string $moveTo = null): array
    {
        try {
            $moved = DB::transaction(function () use ($id, $moveTo): int {
                $statuses = TicketStatus::ordered();
                $status = $statuses->firstWhere('id', $id) ?? throw new Exception('Column not found.', 1);
                if ($statuses->count() === 1) {
                    throw new Exception('A board needs at least one column.', 1);
                }

                $moved = 0;
                if (Ticket::query()->where('status', $status->key)->exists()) {
                    $target = $statuses->first(fn (TicketStatus $s): bool => $s->key === $moveTo && $s->id !== $status->id);
                    if (! $target) {
                        throw new Exception("Choose a column for the tickets in \"{$status->name}\" first.", 1);
                    }
                    $moved = Ticket::query()->where('status', $status->key)->update(['status' => $target->key]);
                }

                $status->delete();

                return $moved;
            });

            $return['success'] = true;
            $return['message'] = $moved ? "Column removed; {$moved} ticket".($moved === 1 ? '' : 's').' moved.' : 'Column removed.';
            $return['data'] = ['moved' => $moved];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
