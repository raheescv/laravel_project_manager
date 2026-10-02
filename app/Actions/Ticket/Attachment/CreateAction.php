<?php

namespace App\Actions\Ticket\Attachment;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\UploadedFile;

class CreateAction
{
    /**
     * @return array{success: bool, message: string, data?: TicketAttachment}
     */
    public function execute(Ticket $ticket, UploadedFile $file): array
    {
        try {
            $path = $file->store('tickets/'.$ticket->id, 'public');

            $attachment = TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => (int) $file->getSize(),
            ]);

            $return['success'] = true;
            $return['message'] = 'Attachment added successfully.';
            $return['data'] = $attachment;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
