<?php

namespace App\Actions\RentOut\Commission;

use App\Actions\Journal\DeleteAction as JournalDeleteAction;
use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOutCommission;
use Exception;

/**
 * Undoes a payment: removes the payment journal and returns the commission to
 * Finance Approved (still payable).
 */
class CancelPaymentAction
{
    public function execute(int $id, int $userId): array
    {
        try {
            $model = RentOutCommission::findOrFail($id);
            if ($model->status !== CommissionStatus::Paid) {
                throw new Exception('This commission has not been paid.', 1);
            }

            if ($model->payment_journal_id) {
                $response = (new JournalDeleteAction())->execute($model->payment_journal_id, $userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $model->update([
                'status' => CommissionStatus::FinanceApproved,
                'payment_account_id' => null,
                'paid_date' => null,
                'paid_by' => null,
                'payment_journal_id' => null,
                'updated_by' => $userId,
            ]);

            $return['success'] = true;
            $return['message'] = 'Commission payment cancelled.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
