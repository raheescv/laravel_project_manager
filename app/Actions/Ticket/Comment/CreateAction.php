<?php

namespace App\Actions\Ticket\Comment;

use App\Actions\Ticket\NotifyParticipantsAction;
use App\Models\Ticket;
use App\Models\TicketComment;

class CreateAction
{
    /**
     * @param  array{comment?: string}  $data
     * @return array{success: bool, message: string, data?: TicketComment}
     */
    public function execute(array $data, int $ticketId, int $userId): array
    {
        try {
            validationHelper(TicketComment::rules(), $data);
            $ticket = Ticket::findOrFail($ticketId);

            $comment = TicketComment::create([
                'ticket_id' => $ticket->id,
                'comment' => $data['comment'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            (new NotifyParticipantsAction())->commented($ticket, $userId, $comment->comment);

            $return['success'] = true;
            $return['message'] = 'Comment added successfully.';
            $return['data'] = $comment;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
