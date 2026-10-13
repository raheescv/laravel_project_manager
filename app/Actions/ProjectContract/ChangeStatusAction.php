<?php

namespace App\Actions\ProjectContract;

use App\Enums\ProjectContract\ContractStatus;
use App\Models\ProjectContract;
use App\Models\ProjectContractPayment;
use Exception;
use Illuminate\Support\Facades\DB;

class ChangeStatusAction
{
    /**
     * Close an active contract as completed, or cancel one that has no
     * payments. A completed contract must not still hold any security.
     *
     * @return array{success: bool, message: string, data?: ProjectContract}
     */
    public function execute(int $id, string $status, int $userId): array
    {
        try {
            $contract = DB::transaction(function () use ($id, $status, $userId): ProjectContract {
                $contract = ProjectContract::with('securities.settlements')->lockForUpdate()->findOrFail($id);
                $target = ContractStatus::from($status);

                if ($target === ContractStatus::Completed) {
                    if (! $contract->isActive()) {
                        throw new Exception('Only an active contract can be completed.');
                    }
                    if ($contract->securities->contains(fn ($security): bool => $security->heldAmount() > 0)) {
                        throw new Exception('Return or forfeit every security before completing this contract.');
                    }
                } elseif ($target === ContractStatus::Cancelled) {
                    if (in_array($contract->status, [ContractStatus::Completed, ContractStatus::Cancelled], true)) {
                        throw new Exception('This contract is already closed.');
                    }
                    if ($contract->payments()->where('status', ProjectContractPayment::STATUS_POSTED)->exists()) {
                        throw new Exception('Reverse the payments on this contract before cancelling it.');
                    }
                } else {
                    throw new Exception('A contract can only be completed or cancelled here.');
                }

                $contract->update(['status' => $target, 'updated_by' => $userId]);

                return $contract->refresh();
            });

            return ['success' => true, 'message' => 'Contract marked '.strtolower($contract->status->label()).'.', 'data' => $contract];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
