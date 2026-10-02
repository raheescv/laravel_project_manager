<?php

namespace App\Actions\Ticket;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteAction
{
    /**
     * @return array{success: bool, message: string, data?: mixed}
     */
    public function execute(int $id): array
    {
        try {
            $paths = DB::transaction(function () use ($id): array {
                $ticket = Ticket::with('attachments')->findOrFail($id);
                $paths = $ticket->attachments->pluck('file_path')->all();

                $ticket->comments()->delete();
                $ticket->attachments()->delete();
                $ticket->delete();

                return $paths;
            });

            Storage::disk('public')->delete($paths);

            $return['success'] = true;
            $return['message'] = 'Ticket deleted successfully.';
            $return['data'] = null;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
