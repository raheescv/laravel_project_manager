<?php

namespace App\Actions\Student;

use App\Actions\Student\Guardian\SyncAction as GuardianSyncAction;
use App\Models\Account;
use App\Models\StudentDetail;
use Exception;

/**
 * Update a student's account, details and parents. The caller owns the transaction.
 *
 * `guardians` is optional: when absent the student's parents are left as they are,
 * so a form that edits only the profile cannot unlink a family by omission.
 */
class UpdateAction
{
    public function execute(array $data, int $accountId, int $userId): array
    {
        try {
            $account = Account::student()->with('studentDetail')->find($accountId);
            if (! $account) {
                throw new Exception("Student not found with the specified ID: $accountId.", 1);
            }

            $accountData = CreateAction::accountData($data);
            $accountData['account_type'] = $account->account_type;
            $accountData['model'] = 'student';

            $detail = $account->studentDetail ?? new StudentDetail(['account_id' => $account->id, 'created_by' => $userId]);
            $detailData = CreateAction::detailData($data);
            $detailData['account_id'] = $account->id;
            $detailData['updated_by'] = $userId;

            validationHelper(Account::rules($account->id), $accountData + $account->only(['name']));
            validationHelper(StudentDetail::rules($detail->id ?? 0), $detailData + $detail->only(['admission_no']));

            // Replacing the card is a card action (see Card\AssignAction), which
            // also clears a block; a profile edit keeps whatever card is linked.
            unset($detailData['card_uid']);

            $account->update($accountData);
            $detail->fill($detailData)->save();

            if (array_key_exists('guardians', $data)) {
                $response = (new GuardianSyncAction())->execute($account->id, $data['guardians'] ?? [], $userId);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Student';
            $return['data'] = $account->refresh()->load('studentDetail', 'guardians');
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
