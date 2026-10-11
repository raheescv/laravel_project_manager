<?php

namespace App\Actions\Ticket\Status;

use App\Models\TicketStatus;
use Exception;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Add a column at the end of the board.
     *
     * @param  array{name: string, color: string, icon: string}  $data
     * @return array{success: bool, message: string, data?: TicketStatus}
     */
    public function execute(array $data): array
    {
        try {
            $status = DB::transaction(function () use ($data): TicketStatus {
                $statuses = TicketStatus::ordered();
                if ($statuses->count() >= TicketStatus::MAX_COLUMNS) {
                    throw new Exception('A board can have at most '.TicketStatus::MAX_COLUMNS.' columns.', 1);
                }

                $name = trim($data['name']);

                return TicketStatus::create([
                    'key' => TicketStatus::keyFor($name),
                    'name' => $name,
                    'color' => strtolower($data['color']),
                    'icon' => $data['icon'],
                    'sort_order' => ($statuses->max('sort_order') ?? -1) + 1,
                ]);
            });

            $return['success'] = true;
            $return['message'] = "Column \"{$status->name}\" added.";
            $return['data'] = $status;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
