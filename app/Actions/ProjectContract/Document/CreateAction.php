<?php

namespace App\Actions\ProjectContract\Document;

use App\Models\ProjectContract;
use App\Models\ProjectContractDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreateAction
{
    /**
     * Store uploaded files on the public disk under the contract's folder.
     *
     * @param  array<int, UploadedFile>  $files
     * @return array{success: bool, message: string, data?: array<int, ProjectContractDocument>}
     */
    public function execute(int $contractId, array $files, ?string $documentType, int $userId): array
    {
        $stored = [];
        try {
            $documents = DB::transaction(function () use ($contractId, $files, $documentType, $userId, &$stored): array {
                $contract = ProjectContract::findOrFail($contractId);
                $documents = [];
                foreach ($files as $file) {
                    $path = $file->store('project-contracts/'.$contract->id, 'public');
                    $stored[] = $path;
                    $documents[] = ProjectContractDocument::create([
                        'project_contract_id' => $contract->id,
                        'document_type' => $documentType,
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'created_by' => $userId,
                    ]);
                }

                return $documents;
            });

            return ['success' => true, 'message' => count($documents) === 1 ? 'Document uploaded.' : count($documents).' documents uploaded.', 'data' => $documents];
        } catch (\Throwable $th) {
            Storage::disk('public')->delete($stored);

            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
