<?php

namespace App\Actions\ProjectContract\Payment;

use App\Actions\ProjectContract\Concerns\PostsJournal;
use App\Models\Account;
use App\Models\ProjectContract;
use App\Models\ProjectContractPayment;
use App\Models\ProjectContractPaymentAllocation;
use App\Models\ProjectContractPaymentTerm;
use Exception;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    use PostsJournal;

    /**
     * Pay the vendor against one or more terms. The amount is spread over the
     * chosen terms oldest-due first and may be a part payment, but never more
     * than their combined balance. Posts Dr Vendor / Cr payment method.
     *
     * @param  array{project_contract_id: int, term_ids: array<int, int>, date: string, amount: float, payment_method_id: int, cheque_no?: string|null, cheque_date?: string|null, bank_name?: string|null, reference?: string|null, remarks?: string|null}  $data
     * @return array{success: bool, message: string, data?: ProjectContractPayment}
     */
    public function execute(array $data, int $userId): array
    {
        try {
            $payment = DB::transaction(function () use ($data, $userId): ProjectContractPayment {
                $contract = ProjectContract::with('vendor')->lockForUpdate()->findOrFail($data['project_contract_id']);
                if (! $contract->isActive()) {
                    throw new Exception('Payments can be recorded on an active contract only.');
                }

                if (! in_array((int) $data['payment_method_id'], array_map('intval', tenant_cache('payment_methods', [])), true)) {
                    throw new Exception('Choose one of the configured payment methods.');
                }

                $terms = ProjectContractPaymentTerm::query()
                    ->where('project_contract_id', $contract->id)
                    ->whereIn('id', $data['term_ids'])
                    ->lockForUpdate()
                    ->orderBy('due_date')
                    ->orderBy('id')
                    ->get();

                if ($terms->count() !== count(array_unique($data['term_ids']))) {
                    throw new Exception('Choose terms that belong to this contract.');
                }

                $amount = round((float) $data['amount'], 2);
                $balance = round((float) $terms->sum('balance'), 2);
                if ($amount <= 0) {
                    throw new Exception('Enter an amount above zero.');
                }
                if ($amount > $balance) {
                    throw new Exception('The amount is more than the '.number_format($balance, 2).' still due on the chosen terms.');
                }

                $payment = ProjectContractPayment::create([
                    'branch_id' => $contract->branch_id,
                    'project_contract_id' => $contract->id,
                    'voucher_no' => ProjectContractPayment::nextVoucherNo(),
                    'date' => $data['date'],
                    'amount' => $amount,
                    'payment_method_id' => $data['payment_method_id'],
                    'cheque_no' => $data['cheque_no'] ?? null,
                    'cheque_date' => $data['cheque_date'] ?? null,
                    'bank_name' => $data['bank_name'] ?? null,
                    'reference' => $data['reference'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'status' => ProjectContractPayment::STATUS_POSTED,
                    'created_by' => $userId,
                ]);

                $remaining = $amount;
                foreach ($terms as $term) {
                    $share = round(min($remaining, (float) $term->balance), 2);
                    if ($share <= 0) {
                        continue;
                    }
                    ProjectContractPaymentAllocation::create([
                        'project_contract_payment_id' => $payment->id,
                        'project_contract_payment_term_id' => $term->id,
                        'amount' => $share,
                    ]);
                    $term->refreshPaid();
                    $remaining = round($remaining - $share, 2);
                }

                $method = Account::find($data['payment_method_id']);
                $journal = $this->postPair(
                    $contract,
                    [
                        'date' => $payment->date->toDateString(),
                        'description' => 'Project contract payment '.$payment->voucher_no.' · '.$contract->reference_no,
                        'model' => 'ProjectContractPayment',
                        'model_id' => $payment->id,
                        'reference_number' => $payment->voucher_no,
                        'remarks' => $payment->remarks,
                    ],
                    $contract->account_id,
                    (int) $data['payment_method_id'],
                    $amount,
                    ($method?->name ?? 'Payment').' paid to '.($contract->vendor?->name ?? 'vendor').' for '.$contract->project_name,
                    $userId,
                    ['cheque_no' => $payment->cheque_no, 'bank_name' => $payment->bank_name, 'cheque_date' => $payment->cheque_date?->toDateString()],
                );

                $payment->update(['journal_id' => $journal->id]);

                return $payment->refresh();
            });

            return ['success' => true, 'message' => 'Payment '.$payment->voucher_no.' recorded.', 'data' => $payment];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
