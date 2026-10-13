<?php

namespace App\Actions\ProjectContract;

use App\Enums\ProjectContract\ContractStatus;
use App\Models\ProjectContract;
use Exception;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    /**
     * Only a contract that never went live can be deleted; an approved one is
     * cancelled or completed instead, so its history stays.
     *
     * @return array{success: bool, message: string, data?: ProjectContract}
     */
    public function execute(int $id, int $userId): array
    {
        try {
            $contract = DB::transaction(function () use ($id, $userId): ProjectContract {
                $contract = ProjectContract::lockForUpdate()->findOrFail($id);

                if (! in_array($contract->status, [ContractStatus::Draft, ContractStatus::Rejected], true)) {
                    throw new Exception('Only draft or rejected contracts can be deleted. Cancel this one instead.');
                }
                if ($contract->payments()->exists() || $contract->securities()->exists()) {
                    throw new Exception('This contract has payments or security on it and cannot be deleted.');
                }

                $contract->updated_by = $userId;
                $contract->save();
                $contract->delete();

                return $contract;
            });

            return ['success' => true, 'message' => 'Contract deleted.', 'data' => $contract];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
