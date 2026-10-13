<?php

namespace App\Actions\ProjectContract\PaymentTerm;

use App\Models\ProjectContract;
use App\Models\ProjectContractPaymentTerm;
use Exception;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Add one payment term. Going over the contract amount or the planned
     * number of terms is allowed; the result carries a warning saying so.
     *
     * @param  array{project_contract_id: int, label: string, due_date: string, amount: float, remarks?: string|null}  $data
     * @return array{success: bool, message: string, data?: ProjectContractPaymentTerm, warnings?: array<int, string>}
     */
    public function execute(array $data, int $userId): array
    {
        try {
            $term = DB::transaction(function () use ($data, $userId): ProjectContractPaymentTerm {
                $contract = ProjectContract::lockForUpdate()->findOrFail($data['project_contract_id']);
                if (! $contract->isActive()) {
                    throw new Exception('Payment terms can be added once the contract is approved and active.');
                }

                return ProjectContractPaymentTerm::create([
                    'branch_id' => $contract->branch_id,
                    'project_contract_id' => $contract->id,
                    'label' => $data['label'],
                    'due_date' => $data['due_date'],
                    'amount' => $data['amount'],
                    'paid' => 0,
                    'remarks' => $data['remarks'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            });

            return ['success' => true, 'message' => 'Payment term added.', 'data' => $term, 'warnings' => TermWarnings::for($term->contract)];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
