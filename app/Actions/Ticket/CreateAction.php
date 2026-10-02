<?php

namespace App\Actions\Ticket;

use App\Actions\Ticket\Attachment\CreateAction as AttachmentCreateAction;
use App\Models\Ticket;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * @param  array{title?: string, description?: ?string, status?: string, group?: ?string, created_at?: mixed}  $data
     * @param  list<UploadedFile>  $files
     * @return array{success: bool, message: string, data?: Ticket}
     */
    public function execute(array $data, int $userId, array $files = []): array
    {
        try {
            $ticket = DB::transaction(function () use ($data, $userId, $files): Ticket {
                $data['status'] = $data['status'] ?? Ticket::STATUS_OPEN;
                $data['group'] = TicketGroupName::normalize($data['group'] ?? null);
                $data['description'] = $data['description'] ?? '';
                validationHelper(Ticket::rules(), $data);

                $ticket = new Ticket([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'status' => $data['status'],
                    'group' => $data['group'],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                if (! empty($data['created_at'])) {
                    $ticket->created_at = $data['created_at'];
                }
                $ticket->save();

                foreach ($files as $file) {
                    $response = (new AttachmentCreateAction())->execute($ticket, $file);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }

                return $ticket;
            });

            $return['success'] = true;
            $return['message'] = 'Ticket created successfully.';
            $return['data'] = $ticket;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
