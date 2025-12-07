<?php

namespace App\Actions\Journal;

use Exception;

class GeneralVoucherJournalEntryAction
{
    public function execute($userId, $data, $id = null)
    {
        try {
            $data['created_by'] = $userId;
            $data['source'] = $data['source'] ?? 'General Voucher';
            $data['description'] = $data['description'] ?? 'General Voucher';

            $entries = [];
            $entries[] = [
                'account_id' => $data['debit_id'],
                'counter_account_id' => $data['credit_id'],
                'debit' => $data['amount'],
                'credit' => 0,
                'created_by' => $userId,
                'remarks' => $data['remarks'] ?? null,
            ];
            $entries[] = [
                'account_id' => $data['credit_id'],
                'counter_account_id' => $data['debit_id'],
                'debit' => 0,
                'credit' => $data['amount'],
                'created_by' => $userId,
                'remarks' => $data['remarks'] ?? null,
            ];
            $data['entries'] = $entries;

            if ($id) {
                $response = (new UpdateAction())->execute($data, $id);
            } else {
                $response = (new CreateAction())->execute($data);
            }
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $return['success'] = true;
            $return['message'] = $id ? 'Successfully Updated General Voucher' : 'Successfully Created General Voucher';
            $return['data'] = $response['data'];
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
