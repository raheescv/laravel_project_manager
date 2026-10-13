<?php

namespace App\Actions\ProjectContract\Extension;

use App\Enums\ProjectContract\ApprovalStatus;
use App\Models\ProjectContractExtension;
use Exception;

class ApplyAction
{
    /**
     * Put an approved extension into effect on its contract. Runs inside the
     * caller's transaction.
     *
     * @return array{success: bool, message: string, data?: ProjectContractExtension}
     */
    public function execute(ProjectContractExtension $extension, int $userId): array
    {
        try {
            if ($extension->applied_at) {
                throw new Exception('This extension is already applied.');
            }

            $contract = $extension->contract()->lockForUpdate()->firstOrFail();
            $contract->update([
                'end_date' => $extension->new_end_date,
                'contract_amount' => round((float) $contract->contract_amount + (float) $extension->additional_amount, 2),
                'no_of_terms' => $contract->no_of_terms + $extension->additional_terms,
                'updated_by' => $userId,
            ]);

            $extension->update(['status' => ApprovalStatus::Approved, 'applied_at' => now()]);

            return ['success' => true, 'message' => 'Contract extended.', 'data' => $extension];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
