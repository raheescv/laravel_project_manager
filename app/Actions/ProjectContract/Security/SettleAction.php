<?php

namespace App\Actions\ProjectContract\Security;

use App\Actions\ProjectContract\Approval\RequestAction;
use App\Enums\ProjectContract\ApprovalStatus;
use App\Enums\ProjectContract\SecurityForm;
use App\Enums\ProjectContract\SettlementType;
use App\Models\ProjectContractSecurity;
use App\Models\ProjectContractSecuritySettlement;
use Exception;
use Illuminate\Support\Facades\DB;

class SettleAction
{
    /**
     * Ask to return or forfeit (part of) a security. It goes through the
     * approval levels and posts only once approved; with no levels it applies
     * at once.
     *
     * @param  array{project_contract_security_id: int, type: string, amount: float, date: string, payment_method_id?: int|null, reason?: string|null}  $data
     * @return array{success: bool, message: string, data?: ProjectContractSecuritySettlement}
     */
    public function execute(array $data, int $userId): array
    {
        try {
            $settlement = DB::transaction(function () use ($data, $userId): ProjectContractSecuritySettlement {
                $security = ProjectContractSecurity::with(['contract', 'settlements'])->lockForUpdate()->findOrFail($data['project_contract_security_id']);
                $type = SettlementType::from($data['type']);
                $amount = round((float) $data['amount'], 2);

                if ($amount <= 0) {
                    throw new Exception('Enter an amount above zero.');
                }
                if ($amount > $security->settleableAmount()) {
                    throw new Exception('Only '.number_format($security->settleableAmount(), 2).' of this security is free to settle.');
                }
                if ($type === SettlementType::Forfeit && blank($data['reason'] ?? null)) {
                    throw new Exception('Give a reason for forfeiting the security.');
                }

                $needsMethod = $security->form === SecurityForm::Cash
                    ? $type === SettlementType::Return
                    : $type === SettlementType::Forfeit;
                $methodId = $needsMethod ? (int) ($data['payment_method_id'] ?? 0) : null;
                if ($needsMethod && ! in_array($methodId, array_map('intval', tenant_cache('payment_methods', [])), true)) {
                    throw new Exception($type === SettlementType::Forfeit && $security->form !== SecurityForm::Cash
                        ? 'Choose the account the encashed guarantee or cheque was paid into.'
                        : 'Choose the payment method for the cash.');
                }

                $settlement = ProjectContractSecuritySettlement::create([
                    'project_contract_security_id' => $security->id,
                    'type' => $type,
                    'amount' => $amount,
                    'date' => $data['date'],
                    'payment_method_id' => $methodId,
                    'reason' => $data['reason'] ?? null,
                    'status' => ApprovalStatus::PendingApproval,
                    'created_by' => $userId,
                ]);

                $response = (new RequestAction())->execute($settlement, $security->contract, $amount, $userId);
                if (! $response['success']) {
                    throw new Exception($response['message']);
                }

                if ($response['data']['levels'] === 0) {
                    $applied = (new ApplySettlementAction())->execute($settlement, $userId);
                    if (! $applied['success']) {
                        throw new Exception($applied['message']);
                    }
                }

                return $settlement->refresh();
            });

            $message = $settlement->status === ApprovalStatus::Approved ? 'Security '.strtolower($settlement->type->label()).'.' : 'Sent for approval.';

            return ['success' => true, 'message' => $message, 'data' => $settlement];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
