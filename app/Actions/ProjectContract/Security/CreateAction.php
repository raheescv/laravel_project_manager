<?php

namespace App\Actions\ProjectContract\Security;

use App\Actions\ProjectContract\Concerns\PostsJournal;
use App\Enums\ProjectContract\ContractStatus;
use App\Enums\ProjectContract\SecurityDirection;
use App\Enums\ProjectContract\SecurityForm;
use App\Models\ProjectContract;
use App\Models\ProjectContractSecurity;
use App\Support\ProjectContract\SystemAccounts;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    use PostsJournal;

    /**
     * Lodge a security on a contract. Cash posts at once — received:
     * Dr payment method / Cr Vendor Security Deposit; paid: Dr Security
     * Deposit Paid / Cr payment method. Guarantees and cheques are tracked only.
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, data?: ProjectContractSecurity}
     */
    public function execute(array $data, int $userId, ?UploadedFile $document = null): array
    {
        try {
            $security = DB::transaction(function () use ($data, $userId, $document): ProjectContractSecurity {
                $contract = ProjectContract::with('vendor')->lockForUpdate()->findOrFail($data['project_contract_id']);
                if (in_array($contract->status, [ContractStatus::Cancelled, ContractStatus::Completed], true)) {
                    throw new Exception('Security cannot be added to a closed contract.');
                }

                $form = SecurityForm::from($data['form']);
                $direction = SecurityDirection::from($data['direction'] ?? SecurityDirection::Received->value);

                if ($form->movesMoney() && ! in_array((int) ($data['payment_method_id'] ?? 0), array_map('intval', tenant_cache('payment_methods', [])), true)) {
                    throw new Exception('Choose the payment method the cash deposit went through.');
                }
                if (! $form->movesMoney() && blank($data['reference_no'] ?? null)) {
                    throw new Exception('Enter the guarantee or cheque number.');
                }

                $security = ProjectContractSecurity::create([
                    'branch_id' => $contract->branch_id,
                    'project_contract_id' => $contract->id,
                    'direction' => $direction,
                    'form' => $form,
                    'purpose' => $data['purpose'],
                    'amount' => round((float) $data['amount'], 2),
                    'payment_method_id' => $form->movesMoney() ? $data['payment_method_id'] : null,
                    'bank_name' => $data['bank_name'] ?? null,
                    'reference_no' => $data['reference_no'] ?? null,
                    'valid_from' => $data['valid_from'] ?? null,
                    'valid_until' => $data['valid_until'] ?? null,
                    'cheque_date' => $data['cheque_date'] ?? null,
                    'received_date' => $data['received_date'] ?? today()->toDateString(),
                    'document_path' => $document?->store('project-contracts/'.$contract->id, 'public'),
                    'renewed_from_id' => $data['renewed_from_id'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'created_by' => $userId,
                ]);

                if ($form->movesMoney()) {
                    $deposit = $direction === SecurityDirection::Received ? SystemAccounts::id(SystemAccounts::VENDOR_SECURITY_DEPOSIT) : SystemAccounts::id(SystemAccounts::SECURITY_DEPOSIT_PAID);
                    [$debit, $credit] = $direction === SecurityDirection::Received
                        ? [(int) $security->payment_method_id, $deposit]
                        : [$deposit, (int) $security->payment_method_id];

                    $journal = $this->postPair(
                        $contract,
                        [
                            'date' => $security->received_date->toDateString(),
                            'description' => 'Security deposit · '.$contract->reference_no,
                            'model' => 'ProjectContractSecurity',
                            'model_id' => $security->id,
                        ],
                        $debit,
                        $credit,
                        (float) $security->amount,
                        $direction === SecurityDirection::Received
                            ? 'Security deposit received from '.($contract->vendor?->name ?? 'vendor').' for '.$contract->project_name
                            : 'Security deposit paid to '.($contract->vendor?->name ?? 'vendor').' for '.$contract->project_name,
                        $userId,
                    );
                    $security->update(['journal_id' => $journal->id]);
                }

                return $security->refresh();
            });

            return ['success' => true, 'message' => 'Security saved.', 'data' => $security];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
