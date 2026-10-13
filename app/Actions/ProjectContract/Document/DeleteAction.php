<?php

namespace App\Actions\ProjectContract\Document;

use App\Models\ProjectContractDocument;

class DeleteAction
{
    /**
     * Soft-deletes the record; the file stays on disk so a restore keeps working.
     *
     * @return array{success: bool, message: string, data?: ProjectContractDocument}
     */
    public function execute(int $id, int $contractId): array
    {
        try {
            $document = ProjectContractDocument::where('project_contract_id', $contractId)->findOrFail($id);
            $document->delete();

            return ['success' => true, 'message' => 'Document deleted.', 'data' => $document];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
