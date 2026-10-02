<?php

namespace App\Actions\Ticket\Comment;

use App\Models\TicketComment;

class DeleteAction
{
    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public function execute(int $ticketId, int $id): array
    {
        try {
            TicketComment::where('ticket_id', $ticketId)->findOrFail($id)->delete();

            $return['success'] = true;
            $return['message'] = 'Comment deleted successfully.';
            $return['data'] = null;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
