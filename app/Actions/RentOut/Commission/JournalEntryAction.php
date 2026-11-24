<?php

namespace App\Actions\RentOut\Commission;

use App\Actions\Journal\CreateAction as JournalCreateAction;
use App\Actions\RentOut\Payment\Concerns\ResolvesRentOutAccounts;
use App\Models\RentOutCommission;
use Exception;

/**
 * Journals for an agent commission:
 *  - approval: Dr Commission Expense / Cr Commission Payable
 *  - payment:  Dr Commission Payable / Cr payment method
 */
class JournalEntryAction
{
    use ResolvesRentOutAccounts;

    public const KIND_APPROVAL = 'approval';

    public const KIND_PAYMENT = 'payment';

    protected int $userId;

    public function execute(RentOutCommission $commission, int $userId, string $kind = self::KIND_APPROVAL): array
    {
        $this->userId = $userId;
        try {
            $commission->loadMissing(['rentOut', 'agent']);
            $rentOut = $commission->rentOut;
            $amount = round((float) $commission->amount, 2);
            if ($amount <= 0) {
                throw new Exception('Commission amount must be greater than zero.', 1);
            }

            $payableId = $this->commissionPayableAccountId($rentOut);
            $agentName = $commission->agent?->name ?? 'agent';
            $agreement = $rentOut->agreement_no;

            if ($kind === self::KIND_PAYMENT) {
                $paymentAccountId = $this->tenantAccountId($rentOut, $commission->payment_account_id);
                if (! $paymentAccountId) {
                    throw new Exception('Payment method is required.', 1);
                }
                $date = $commission->paid_date?->format('Y-m-d') ?? now()->format('Y-m-d');
                $description = 'Commission Payment';
                $remarks = 'Commission paid to '.$agentName.' for '.$agreement;
                $entries = $this->makeEntryPair($payableId, $paymentAccountId, $amount, 0, $remarks, $commission->id);
            } else {
                $date = $commission->finance_approved_at?->format('Y-m-d') ?? now()->format('Y-m-d');
                $description = 'Commission Expense';
                $remarks = 'Commission payable to '.$agentName.' for '.$agreement;
                $entries = $this->makeEntryPair($this->commissionExpenseAccountId($rentOut), $payableId, $amount, 0, $remarks, $commission->id);
            }

            $response = (new JournalCreateAction())->execute([
                'tenant_id' => $commission->tenant_id,
                'branch_id' => $commission->branch_id,
                'date' => $date,
                'description' => $description,
                'remarks' => $remarks,
                'reference_number' => $commission->invoice_no,
                'person_name' => $commission->agent?->name,
                'source' => 'expense',
                'model' => RentOutCommission::JOURNAL_MODEL,
                'model_id' => $commission->id,
                'created_by' => $userId,
                'entries' => $entries,
            ]);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $commission->update([$kind === self::KIND_PAYMENT ? 'payment_journal_id' : 'journal_id' => $response['data']->id]);

            $return['success'] = true;
            $return['message'] = 'Commission journal posted.';
            $return['data'] = $response['data'];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function makeEntryPair(int $accountId1, int $accountId2, float $debit, float $credit, string $remarks, int $modelId): array
    {
        $base = ['created_by' => $this->userId, 'remarks' => $remarks, 'model' => RentOutCommission::JOURNAL_MODEL, 'model_id' => $modelId];

        return [
            array_merge($base, ['account_id' => $accountId1, 'counter_account_id' => $accountId2, 'debit' => $debit, 'credit' => $credit]),
            array_merge($base, ['account_id' => $accountId2, 'counter_account_id' => $accountId1, 'debit' => $credit, 'credit' => $debit]),
        ];
    }
}
