<?php

namespace App\Actions\Ticket\Comment;

use App\Models\TicketComment;

class UpdateAction
{
    /**
     * @param  array{comment?: string}  $data
     * @return array{success: bool, message: string, data?: TicketComment}
     */
    public function execute(array $data, int $ticketId, int $id, int $userId): array
    {
        try {
            validationHelper(TicketComment::rules(), $data);
            $comment = TicketComment::where('ticket_id', $ticketId)->findOrFail($id);

            $comment->update([
                'comment' => $data['comment'],
                'updated_by' => $userId,
            ]);

            $return['success'] = true;
            $return['message'] = 'Comment updated successfully.';
            $return['data'] = $comment;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
