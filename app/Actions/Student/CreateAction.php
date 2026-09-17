<?php

namespace App\Actions\Student;

use App\Actions\Student\Guardian\SyncAction as GuardianSyncAction;
use App\Models\Account;
use App\Models\StudentDetail;
use Exception;

/**
 * Enrol a student: the student's account, its student_details row and the
 * parents who may see it.
 *
 * The account is the student — name, mobile, email, dob, id_no (QID), nationality,
 * image and description stay on `accounts` — and its ledger holds the card
 * balance. Only the school-only fields go to student_details.
 *
 * The caller owns the transaction (see the action-layer convention).
 */
class CreateAction
{
    /** Columns a student writes on `accounts`. */
    public const ACCOUNT_FIELDS = ['name', 'mobile', 'email', 'dob', 'id_no', 'nationality', 'image', 'description'];

    /** Columns a student writes on `student_details`. */
    public const DETAIL_FIELDS = ['admission_no', 'gender', 'grade', 'section', 'status', 'card_uid'];

    public function execute(array $data, int $userId): array
    {
        try {
            $setup = (new EnsureAccountsAction())->execute();

            $accountData = self::accountData($data);
            $accountData['account_type'] = 'liability';
            $accountData['model'] = 'student';
            $accountData['account_category_id'] = $setup['category_id'];

            $detailData = self::detailData($data);
            $detailData['account_id'] = 0;
            $detailData['created_by'] = $detailData['updated_by'] = $userId;

            // Validate both halves before writing either, so a duplicate admission
            // number never leaves an orphan account behind.
            validationHelper(Account::rules(), $accountData);
            validationHelper(StudentDetail::rules(), $detailData);

            // Deliberately not App\Actions\Account\CreateAction: its duplicate check
            // is name + mobile, and schools have many students who share a name and
            // have no mobile of their own. A student's identity is the admission number.
            $account = Account::create($accountData);

            $detailData['account_id'] = $account->id;
            $account->studentDetail()->create($detailData);

            $response = (new GuardianSyncAction())->execute($account->id, $data['guardians'] ?? [], $userId);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Created Student';
            $return['data'] = $account->load('studentDetail', 'guardians');
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    public static function accountData(array $data): array
    {
        $account = array_intersect_key($data, array_flip(self::ACCOUNT_FIELDS));
        foreach (['mobile', 'email', 'dob', 'id_no', 'nationality', 'description'] as $field) {
            if (array_key_exists($field, $account) && $account[$field] === '') {
                $account[$field] = null;
            }
        }

        return $account;
    }

    public static function detailData(array $data): array
    {
        $detail = array_intersect_key($data, array_flip(self::DETAIL_FIELDS));
        $detail['status'] = ($detail['status'] ?? '') ?: 'active';
        if (array_key_exists('card_uid', $detail)) {
            $detail['card_uid'] = StudentDetail::normalizeCardUid($detail['card_uid']);
        }
        foreach (['gender', 'grade', 'section'] as $field) {
            if (array_key_exists($field, $detail) && $detail[$field] === '') {
                $detail[$field] = null;
            }
        }

        return $detail;
    }
}
