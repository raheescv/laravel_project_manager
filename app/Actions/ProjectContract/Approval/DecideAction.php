<?php

namespace App\Actions\ProjectContract\Approval;

use App\Actions\ProjectContract\Extension\ApplyAction as ExtensionApplyAction;
use App\Actions\ProjectContract\Security\ApplySettlementAction;
use App\Enums\ProjectContract\ApprovalStatus;
use App\Enums\ProjectContract\ContractStatus;
use App\Models\ProjectContract;
use App\Models\ProjectContractApproval;
use App\Models\ProjectContractExtension;
use App\Models\ProjectContractSecuritySettlement;
use App\Models\User;
use App\Support\ProjectContract\ApprovalGate;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DecideAction
{
    /**
     * Approve or reject the current level of a submission. Approving the last
     * level puts the change into effect; a rejection needs a comment and ends
     * the round.
     *
     * @return array{success: bool, message: string, data?: Model}
     */
    public function execute(Model $approvable, string $decision, ?string $comment, User $user): array
    {
        try {
            $message = DB::transaction(function () use ($approvable, $decision, $comment, $user): string {
                $approvable = $approvable->newQuery()->lockForUpdate()->findOrFail($approvable->getKey());
                $current = ApprovalGate::current($approvable) ?? throw new Exception('Nothing is waiting for approval here.');

                if ($refusal = ApprovalGate::refusal($user, $current)) {
                    throw new Exception($refusal);
                }

                if ($decision === 'reject') {
                    if (blank($comment)) {
                        throw new Exception('Give a reason for the rejection.');
                    }
                    $current->update(['status' => ProjectContractApproval::REJECTED, 'acted_by' => $user->id, 'acted_at' => now(), 'comment' => $comment]);
                    ProjectContractApproval::query()
                        ->where('approvable_type', $approvable::class)
                        ->where('approvable_id', $approvable->getKey())
                        ->where('status', ProjectContractApproval::PENDING)
                        ->update(['status' => ProjectContractApproval::SUPERSEDED]);
                    $this->reject($approvable, $user->id);

                    return 'Rejected.';
                }

                if ($decision !== 'approve') {
                    throw new Exception('Choose approve or reject.');
                }

                $current->update(['status' => ProjectContractApproval::APPROVED, 'acted_by' => $user->id, 'acted_at' => now(), 'comment' => $comment]);

                if (ApprovalGate::current($approvable) !== null) {
                    return "Level {$current->level} approved. Waiting for the next level.";
                }

                $this->finalise($approvable, $user->id);

                return 'Approved.';
            });

            return ['success' => true, 'message' => $message, 'data' => $approvable->fresh()];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    private function finalise(Model $approvable, int $userId): void
    {
        $response = match (true) {
            $approvable instanceof ProjectContract => $this->activate($approvable, $userId),
            $approvable instanceof ProjectContractExtension => (new ExtensionApplyAction())->execute($approvable, $userId),
            $approvable instanceof ProjectContractSecuritySettlement => (new ApplySettlementAction())->execute($approvable, $userId),
            default => ['success' => false, 'message' => 'This item cannot be approved.'],
        };

        if (! $response['success']) {
            throw new Exception($response['message']);
        }
    }

    /**
     * @return array{success: bool, message: string}
     */
    private function activate(ProjectContract $contract, int $userId): array
    {
        $contract->update(['status' => ContractStatus::Active, 'approved_at' => now(), 'updated_by' => $userId]);

        return ['success' => true, 'message' => 'Contract approved.'];
    }

    private function reject(Model $approvable, int $userId): void
    {
        if ($approvable instanceof ProjectContract) {
            $approvable->update(['status' => ContractStatus::Rejected, 'updated_by' => $userId]);

            return;
        }

        $approvable->update(['status' => ApprovalStatus::Rejected]);
    }
}
