<?php

namespace App\Actions\Ticket;

use App\Actions\Ticket\Attachment\CreateAction as AttachmentCreateAction;
use App\Models\Ticket;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    /**
     * @param  array{title?: string, description?: ?string, status?: string, group?: ?string}  $data
     * @param  list<UploadedFile>  $files
     * @return array{success: bool, message: string, data?: Ticket}
     */
    public function execute(array $data, int $id, int $userId, array $files = []): array
    {
        try {
            $ticket = DB::transaction(function () use ($data, $id, $userId, $files): Ticket {
                $ticket = Ticket::findOrFail($id);

                $data = array_merge($ticket->only(['title', 'description', 'status', 'group']), $data);
                $data['group'] = TicketGroupName::normalize($data['group'] ?? null);
                $data['description'] = $data['description'] ?? '';
                validationHelper(Ticket::rules($id), $data);

                $ticket->update([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'status' => $data['status'],
                    'group' => $data['group'],
                    'updated_by' => $userId,
                ]);

                foreach ($files as $file) {
                    $response = (new AttachmentCreateAction())->execute($ticket, $file);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }

                return $ticket;
            });

            $return['success'] = true;
            $return['message'] = 'Ticket updated successfully.';
            $return['data'] = $ticket;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
