<?php

namespace App\Actions\ProjectContract;

use App\Enums\ProjectContract\ContractStatus;
use App\Models\Account;
use App\Models\ProjectContract;
use Exception;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Save a new contract as a draft; with `submit` true it goes straight to
     * the approval levels.
     *
     * @param  array{project_name: string, account_id: int, start_date: string, end_date: string, no_of_terms: int, payment_frequency: string, contract_amount: float, remark?: string|null, branch_id?: int|null, submit?: bool}  $data
     * @return array{success: bool, message: string, data?: ProjectContract}
     */
    public function execute(array $data, int $userId): array
    {
        try {
            $contract = DB::transaction(function () use ($data, $userId): ProjectContract {
                validationHelper(ProjectContract::rules(), $data);

                if (! Account::vendor()->whereKey($data['account_id'])->exists()) {
                    throw new Exception('Choose a vendor account for this contract.');
                }

                $branchId = $data['branch_id'] ?? session('branch_id');
                if (! $branchId) {
                    throw new Exception('Select a branch before creating a contract.');
                }

                $contract = ProjectContract::create([
                    'branch_id' => $branchId,
                    'reference_no' => ProjectContract::nextReferenceNo(),
                    'project_name' => $data['project_name'],
                    'account_id' => $data['account_id'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'no_of_terms' => $data['no_of_terms'],
                    'payment_frequency' => $data['payment_frequency'],
                    'contract_amount' => $data['contract_amount'],
                    'remark' => $data['remark'] ?? null,
                    'status' => ContractStatus::Draft,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                if (! empty($data['submit'])) {
                    $response = (new SubmitAction())->execute($contract->id, $userId);
                    if (! $response['success']) {
                        throw new Exception($response['message']);
                    }
                }

                return $contract->refresh();
            });

            return [
                'success' => true,
                'message' => $contract->status === ContractStatus::Draft ? 'Contract saved as a draft.' : 'Contract created and sent for approval.',
                'data' => $contract,
            ];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
