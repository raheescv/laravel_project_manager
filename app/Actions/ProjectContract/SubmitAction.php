<?php

namespace App\Actions\ProjectContract;

use App\Actions\ProjectContract\Approval\RequestAction;
use App\Enums\ProjectContract\ContractStatus;
use App\Models\ProjectContract;
use Exception;
use Illuminate\Support\Facades\DB;

class SubmitAction
{
    /**
     * Send a draft (or rejected) contract to the approval levels. With no
     * level configured for its amount it becomes active straight away.
     *
     * @return array{success: bool, message: string, data?: ProjectContract}
     */
    public function execute(int $id, int $userId): array
    {
        try {
            $contract = DB::transaction(function () use ($id, $userId): ProjectContract {
                $contract = ProjectContract::lockForUpdate()->findOrFail($id);

                if (! $contract->status->isEditable()) {
                    throw new Exception('Only a draft or rejected contract can be sent for approval.');
                }

                $contract->update([
                    'status' => ContractStatus::PendingApproval,
                    'submitted_at' => now(),
                    'submitted_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $response = (new RequestAction())->execute($contract, $contract, (float) $contract->contract_amount, $userId);
                if (! $response['success']) {
                    throw new Exception($response['message']);
                }

                if ($response['data']['levels'] === 0) {
                    $contract->update(['status' => ContractStatus::Active, 'approved_at' => now()]);
                }

                return $contract->refresh();
            });

            $message = $contract->status === ContractStatus::Active ? 'Contract approved and active.' : 'Contract sent for approval.';

            return ['success' => true, 'message' => $message, 'data' => $contract];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
