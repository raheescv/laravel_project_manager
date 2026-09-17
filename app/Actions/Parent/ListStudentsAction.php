<?php

namespace App\Actions\Parent;

use App\Actions\Student\GetBalanceAction;
use App\Models\Account;
use App\Models\Guardian;
use App\Support\Student\StudentSettings;

/** The parent portal home: each of the parent's children with card status and balance. */
class ListStudentsAction
{
    /** @return array<int, array> */
    public function execute(Guardian $guardian): array
    {
        $students = $guardian->students()->with('studentDetail')->orderBy('accounts.name')->get();
        $balances = (new GetBalanceAction())->many($students->pluck('id')->all());
        $limit = StudentSettings::current()->overdraftLimit;

        return $students->map(fn ($account) => self::summary($account, $balances[$account->id] ?? 0.0, $limit))->all();
    }

    /** One child as the portal shows it. [$account] is loaded through Guardian::students() with studentDetail. */
    public static function summary(Account $account, float $balance, float $overdraftLimit): array
    {
        $detail = $account->studentDetail;

        return [
            'account_id' => $account->id,
            'name' => $account->name,
            'image_url' => $account->image ? $account->image_url : null,
            'admission_no' => $detail?->admission_no,
            'class' => $detail?->classLabel(),
            'status' => $detail?->status,
            'has_card' => (bool) $detail?->card_uid,
            'card_blocked' => (bool) $detail?->isCardBlocked(),
            'balance' => $balance,
            'available' => max(0, round($balance + $overdraftLimit, 2)),
            'relation' => $account->pivot?->relation,
        ];
    }
}
