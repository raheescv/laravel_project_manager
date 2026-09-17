<?php

namespace App\Actions\Student\Guardian;

use App\Models\Account;
use App\Models\Guardian;
use Exception;
use Illuminate\Validation\Rule;

/**
 * Make a student's linked parents exactly the given list.
 *
 * A parent is matched by mobile number within the school, so brothers and sisters
 * enrolled separately end up under ONE login that shows all of them. Parents who
 * drop off a student's list are only unlinked; their login stays for any other
 * child. The caller owns the transaction.
 *
 * @param  array<int, array{name?: string, mobile?: string, email?: ?string, relation?: string, is_primary?: bool}>  $guardians
 */
class SyncAction
{
    public function execute(int $accountId, array $guardians, int $userId): array
    {
        try {
            $account = Account::student()->find($accountId);
            if (! $account) {
                throw new Exception("Student not found with the specified ID: $accountId.", 1);
            }

            $rows = array_values(array_filter($guardians, fn ($row) => filled($row['name'] ?? null) || filled($row['mobile'] ?? null)));

            $sync = [];
            $hasPrimary = false;
            foreach ($rows as $index => $row) {
                $row['mobile'] = self::normalizeMobile($row['mobile'] ?? '');
                $row['email'] = filled($row['email'] ?? null) ? trim($row['email']) : null;
                $row['relation'] = ($row['relation'] ?? '') ?: 'guardian';

                validationHelper(self::rules(), $row);

                $guardian = Guardian::where('mobile', $row['mobile'])->first();
                if ($guardian) {
                    $guardian->fill(['name' => $row['name'], 'email' => $row['email'] ?? $guardian->email, 'updated_by' => $userId])->save();
                } else {
                    $guardian = Guardian::create([
                        'name' => $row['name'],
                        'mobile' => $row['mobile'],
                        'email' => $row['email'],
                        'status' => 'active',
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }

                if (isset($sync[$guardian->id])) {
                    throw new Exception("{$row['mobile']} is listed twice for this student.", 1);
                }

                $isPrimary = ! $hasPrimary && (bool) ($row['is_primary'] ?? false);
                $hasPrimary = $hasPrimary || $isPrimary;
                $sync[$guardian->id] = ['relation' => $row['relation'], 'is_primary' => $isPrimary];
            }

            // Someone must receive the invite and the notices: default to the first parent.
            if (! $hasPrimary && $sync) {
                $sync[array_key_first($sync)]['is_primary'] = true;
            }

            $account->guardians()->sync($sync);

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Parents';
            $return['data'] = $account->guardians()->get();
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /** One parent row, after normalizeMobile() and the relation default. */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'max:100'],
            'mobile' => ['required', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'relation' => ['required', Rule::in(array_keys(Guardian::RELATIONS))],
        ];
    }

    /** Digits only (a leading + kept), so "5512 3456" and "55123456" are one parent. */
    public static function normalizeMobile(?string $mobile): string
    {
        $mobile = trim((string) $mobile);
        $plus = str_starts_with($mobile, '+') ? '+' : '';

        return $plus.preg_replace('/\D/', '', $mobile);
    }
}
