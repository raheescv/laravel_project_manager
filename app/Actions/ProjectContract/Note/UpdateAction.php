<?php

namespace App\Actions\ProjectContract\Note;

use App\Models\ProjectContractNote;
use Exception;

class UpdateAction
{
    /**
     * Only the author edits a note.
     *
     * @return array{success: bool, message: string, data?: ProjectContractNote}
     */
    public function execute(int $id, int $contractId, string $note, int $userId): array
    {
        try {
            $model = ProjectContractNote::where('project_contract_id', $contractId)->findOrFail($id);
            if ($model->created_by !== $userId) {
                throw new Exception('Only the person who wrote a note can edit it.');
            }

            $model->update(['note' => trim($note), 'updated_by' => $userId]);

            return ['success' => true, 'message' => 'Note updated.', 'data' => $model];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
