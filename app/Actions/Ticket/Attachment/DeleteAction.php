<?php

namespace App\Actions\Ticket\Attachment;

use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;

class DeleteAction
{
    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public function execute(int $ticketId, int $id): array
    {
        try {
            $attachment = TicketAttachment::where('ticket_id', $ticketId)->findOrFail($id);
            $attachment->delete();
            Storage::disk('public')->delete($attachment->file_path);

            $return['success'] = true;
            $return['message'] = 'Attachment removed successfully.';
            $return['data'] = null;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
