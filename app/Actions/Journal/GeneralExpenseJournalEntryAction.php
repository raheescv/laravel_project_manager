<?php

namespace App\Actions\Journal;

use App\Models\Account;

class GeneralExpenseJournalEntryAction
{
    public function execute($userId, $data, $id = null)
    {
        try {
            $data['created_by'] = $userId;

            $entries = [];
            $entries[] = [
                'account_id' => $data['debit'],
                'counter_account_id' => $data['credit'],
                'debit' => $data['amount'],
                'credit' => 0,
                'created_by' => $userId,
                'remarks' => ucfirst(Account::find($data['debit'])->name).' Expense',
            ];
            $entries[] = [
                'account_id' => $data['credit'],
                'counter_account_id' => $data['debit'],
                'debit' => 0,
                'credit' => $data['amount'],
                'created_by' => $userId,
                'remarks' => 'Paid In '.Account::find($data['credit'])->name,
            ];
            $data['entries'] = $entries;

            $response = (new CreateAction())->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Expense';
            $return['data'] = $data;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
