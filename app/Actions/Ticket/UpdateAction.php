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
            $changes = [];
            $ticket = DB::transaction(function () use ($data, $id, $userId, $files, &$changes): Ticket {
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
                $changes = array_keys($ticket->getChanges());

                foreach ($files as $file) {
                    $response = (new AttachmentCreateAction())->execute($ticket, $file);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }

                if ($files) {
                    $changes[] = 'attachments';
                }

                return $ticket;
            });

            $this->notify($ticket, $userId, $changes);

            $return['success'] = true;
            $return['message'] = 'Ticket updated successfully.';
            $return['data'] = $ticket;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * A move between columns is announced as a status change; anything else as an edit.
     *
     * @param  list<string>  $changes
     */
    private function notify(Ticket $ticket, int $userId, array $changes): void
    {
        $changes = array_values(array_diff($changes, ['updated_by', 'updated_at']));
        if (! $changes) {
            return;
        }

        $changes === ['status']
            ? (new NotifyParticipantsAction())->statusChanged($ticket, $userId)
            : (new NotifyParticipantsAction())->edited($ticket, $userId);
    }
}
