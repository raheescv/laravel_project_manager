<?php

namespace App\Actions\RentOut\Commission;

use App\Actions\Journal\DeleteAction as JournalDeleteAction;
use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOutCommission;
use Exception;

/**
 * Pulls an unpaid, finance-approved commission back to Draft so it can be
 * corrected: the expense journal is removed and approvals are cleared.
 */
class ReopenAction
{
    public function execute(int $id, int $userId): array
    {
        try {
            $model = RentOutCommission::findOrFail($id);
            if ($model->status === CommissionStatus::Paid) {
                throw new Exception('Cancel the payment before reopening this commission.', 1);
            }
            if ($model->status !== CommissionStatus::FinanceApproved) {
                throw new Exception('Only a finance-approved commission can be reopened.', 1);
            }

            if ($model->journal_id) {
                $response = (new JournalDeleteAction())->execute($model->journal_id, $userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $model->update([
                'status' => CommissionStatus::Draft,
                'journal_id' => null,
                'submitted_by' => null,
                'submitted_at' => null,
                'ceo_approved_by' => null,
                'ceo_approved_at' => null,
                'owner_approved_by' => null,
                'owner_approved_at' => null,
                'finance_approved_by' => null,
                'finance_approved_at' => null,
                'updated_by' => $userId,
            ]);

            $return['success'] = true;
            $return['message'] = 'Commission reopened as draft.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
