<?php

namespace App\Actions\Ticket\Status;

use App\Models\TicketStatus;

class UpdateAction
{
    /**
     * Rename or restyle a column. The key stays, so its tickets stay with it.
     *
     * @param  array{name?: string, color?: string, icon?: string}  $data
     * @return array{success: bool, message: string, data?: TicketStatus}
     */
    public function execute(int $id, array $data): array
    {
        try {
            $status = TicketStatus::findOrFail($id);
            $status->update(array_filter([
                'name' => isset($data['name']) ? trim($data['name']) : null,
                'color' => isset($data['color']) ? strtolower($data['color']) : null,
                'icon' => $data['icon'] ?? null,
            ], fn ($value): bool => $value !== null));

            $return['success'] = true;
            $return['message'] = "Column \"{$status->name}\" updated.";
            $return['data'] = $status;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
