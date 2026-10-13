<?php

namespace App\Actions\ProjectContract\Security;

use App\Actions\ProjectContract\Concerns\PostsJournal;
use App\Enums\ProjectContract\ApprovalStatus;
use App\Enums\ProjectContract\SecurityDirection;
use App\Enums\ProjectContract\SecurityForm;
use App\Enums\ProjectContract\SettlementType;
use App\Models\ProjectContractSecuritySettlement;
use App\Support\ProjectContract\SystemAccounts;
use Exception;

class ApplySettlementAction
{
    use PostsJournal;

    /**
     * Post an approved return or forfeit. Runs inside the caller's transaction.
     *
     *  Received cash   return  : Dr Vendor Security Deposit / Cr payment method
     *                  forfeit : Dr Vendor Security Deposit / Cr Forfeited Deposits
     *  Received guarantee/cheque
     *                  return  : nothing to post (the paper goes back)
     *                  forfeit : Dr payment method / Cr Forfeited Deposits (encashed)
     *  Paid cash       return  : Dr payment method / Cr Security Deposit Paid
     *                  forfeit : Dr Security Deposit Loss / Cr Security Deposit Paid
     *  Paid guarantee/cheque
     *                  return  : nothing to post
     *                  forfeit : Dr Security Deposit Loss / Cr payment method (the vendor encashed it)
     *
     * @return array{success: bool, message: string, data?: ProjectContractSecuritySettlement}
     */
    public function execute(ProjectContractSecuritySettlement $settlement, int $userId): array
    {
        try {
            if ($settlement->applied_at) {
                throw new Exception('This settlement is already applied.');
            }

            $security = $settlement->security()->with('contract.vendor')->firstOrFail();
            $contract = $security->contract;
            $vendor = $contract->vendor?->name ?? 'vendor';
            $returning = $settlement->type === SettlementType::Return;

            $legs = match (true) {
                $security->direction === SecurityDirection::Received && $security->form === SecurityForm::Cash => $returning
                    ? [SystemAccounts::id(SystemAccounts::VENDOR_SECURITY_DEPOSIT), $settlement->payment_method_id, "Security deposit returned to {$vendor}"]
                    : [SystemAccounts::id(SystemAccounts::VENDOR_SECURITY_DEPOSIT), SystemAccounts::id(SystemAccounts::FORFEITED_DEPOSITS), "Security deposit of {$vendor} forfeited: {$settlement->reason}"],
                $security->direction === SecurityDirection::Received => $returning
                    ? null
                    : [$settlement->payment_method_id, SystemAccounts::id(SystemAccounts::FORFEITED_DEPOSITS), $security->form->label()." of {$vendor} encashed: {$settlement->reason}"],
                $security->form === SecurityForm::Cash => $returning
                    ? [$settlement->payment_method_id, SystemAccounts::id(SystemAccounts::SECURITY_DEPOSIT_PAID), "Security deposit refunded by {$vendor}"]
                    : [SystemAccounts::id(SystemAccounts::SECURITY_DEPOSIT_LOSS), SystemAccounts::id(SystemAccounts::SECURITY_DEPOSIT_PAID), "Security deposit paid to {$vendor} not refunded: {$settlement->reason}"],
                default => $returning
                    ? null
                    : [SystemAccounts::id(SystemAccounts::SECURITY_DEPOSIT_LOSS), $settlement->payment_method_id, $security->form->label()." given to {$vendor} was encashed: {$settlement->reason}"],
            };

            $journalId = null;
            if ($legs) {
                [$debit, $credit, $remarks] = $legs;
                if (! $debit || ! $credit) {
                    throw new Exception('Choose a payment method for this settlement.');
                }
                $journalId = $this->postPair(
                    $contract,
                    [
                        'date' => $settlement->date->toDateString(),
                        'description' => 'Security '.strtolower($settlement->type->label()).' · '.$contract->reference_no,
                        'model' => 'ProjectContractSecuritySettlement',
                        'model_id' => $settlement->id,
                        'remarks' => $settlement->reason,
                    ],
                    (int) $debit,
                    (int) $credit,
                    (float) $settlement->amount,
                    $remarks.' ('.$contract->project_name.')',
                    $userId,
                )->id;
            }

            $settlement->update(['status' => ApprovalStatus::Approved, 'applied_at' => now(), 'journal_id' => $journalId]);

            return ['success' => true, 'message' => 'Security '.strtolower($settlement->type->label()).'.', 'data' => $settlement];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
