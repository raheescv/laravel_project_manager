<?php

namespace App\Actions\ProjectContract\Note;

use App\Models\ProjectContract;
use App\Models\ProjectContractNote;

class CreateAction
{
    /**
     * @return array{success: bool, message: string, data?: ProjectContractNote}
     */
    public function execute(int $contractId, string $note, int $userId): array
    {
        try {
            $contract = ProjectContract::findOrFail($contractId);
            $model = ProjectContractNote::create([
                'project_contract_id' => $contract->id,
                'note' => trim($note),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            return ['success' => true, 'message' => 'Note added.', 'data' => $model];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
