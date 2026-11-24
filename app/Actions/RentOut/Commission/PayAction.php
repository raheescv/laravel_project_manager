<?php

namespace App\Actions\RentOut\Commission;

use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOutCommission;
use Exception;

class PayAction
{
    /**
     * @param  array{payment_account_id: int|string, paid_date: string}  $data
     */
    public function execute(int $id, array $data, int $userId): array
    {
        try {
            $model = RentOutCommission::findOrFail($id);
            if ($model->status !== CommissionStatus::FinanceApproved) {
                throw new Exception('Only a finance-approved commission can be paid.', 1);
            }
            validationHelper([
                'payment_account_id' => ['required'],
                'paid_date' => ['required', 'date'],
            ], $data, null, [
                'payment_account_id.required' => 'Payment method is required.',
                'paid_date.required' => 'Payment date is required.',
            ]);

            $model->update([
                'status' => CommissionStatus::Paid,
                'payment_account_id' => (int) $data['payment_account_id'],
                'paid_date' => $data['paid_date'],
                'paid_by' => $userId,
                'updated_by' => $userId,
            ]);

            $response = (new JournalEntryAction())->execute($model->refresh(), $userId, JournalEntryAction::KIND_PAYMENT);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $return['success'] = true;
            $return['message'] = 'Commission paid.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
