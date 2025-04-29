<?php

namespace App\Actions\Journal;

use App\Models\Account;
use Exception;

class GeneralIncomeJournalEntryAction
{
    public function execute($userId, $data, $id = null)
    {
        try {
            $data['created_by'] = $userId;

            $entries = [];
            $entries[] = [
                'account_id' => $data['credit'],
                'counter_account_id' => $data['debit'],
                'credit' => $data['amount'],
                'debit' => 0,
                'created_by' => $userId,
                'remarks' => ucfirst(Account::find($data['credit'])->name).' Income',
            ];
            $entries[] = [
                'account_id' => $data['debit'],
                'counter_account_id' => $data['credit'],
                'credit' => 0,
                'debit' => $data['amount'],
                'created_by' => $userId,
                'remarks' => 'Received by '.Account::find($data['debit'])->name,
            ];
            $data['entries'] = $entries;

            $response = (new CreateAction())->execute($data);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Income';
            $return['data'] = $data;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
