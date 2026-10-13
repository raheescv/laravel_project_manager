<?php

namespace App\Actions\ProjectContract\Note;

use App\Models\ProjectContractNote;
use Exception;

class DeleteAction
{
    /**
     * Only the author deletes a note.
     *
     * @return array{success: bool, message: string, data?: ProjectContractNote}
     */
    public function execute(int $id, int $contractId, int $userId): array
    {
        try {
            $model = ProjectContractNote::where('project_contract_id', $contractId)->findOrFail($id);
            if ($model->created_by !== $userId) {
                throw new Exception('Only the person who wrote a note can delete it.');
            }

            $model->update(['updated_by' => $userId]);
            $model->delete();

            return ['success' => true, 'message' => 'Note deleted.', 'data' => $model];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
