<?php

namespace App\Actions\ProjectContract\Approval;

use App\Models\ProjectContract;
use App\Models\ProjectContractApproval;
use App\Support\ProjectContract\ApprovalLevels;
use Illuminate\Database\Eloquent\Model;

class RequestAction
{
    /**
     * Open a fresh approval round for a submission: one pending row per level
     * the amount calls for. Rows left over from an earlier round are closed as
     * superseded so the history stays readable.
     *
     * @return array{success: bool, message: string, data?: array{levels: int}}
     */
    public function execute(Model $approvable, ProjectContract $contract, float $amount, int $userId): array
    {
        try {
            $previous = ProjectContractApproval::query()
                ->where('approvable_type', $approvable::class)
                ->where('approvable_id', $approvable->getKey());

            (clone $previous)->where('status', ProjectContractApproval::PENDING)->update(['status' => ProjectContractApproval::SUPERSEDED]);
            $round = (int) (clone $previous)->max('round') + 1;

            $levels = ApprovalLevels::for($amount);
            foreach ($levels as $level) {
                ProjectContractApproval::create([
                    'tenant_id' => $contract->tenant_id,
                    'project_contract_id' => $contract->id,
                    'approvable_type' => $approvable::class,
                    'approvable_id' => $approvable->getKey(),
                    'round' => $round,
                    'level' => $level['level'],
                    'approver_role' => $level['role'],
                    'status' => ProjectContractApproval::PENDING,
                    'submitted_by' => $userId,
                ]);
            }

            return ['success' => true, 'message' => 'Sent for approval.', 'data' => ['levels' => count($levels)]];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
