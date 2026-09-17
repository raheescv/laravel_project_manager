<?php

namespace App\Actions\QPay;

use App\Models\Account;
use App\Models\Guardian;
use App\Models\QpayTransaction;
use App\Services\Payment\QPayClient;
use App\Support\Payment\QPaySettings;
use App\Support\Student\StudentSettings;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Open a QPay payment to top up a student's card.
 *
 * Nothing is credited here: the row is the record of a payment the parent is
 * about to make on QPay's page. The caller has already resolved the student
 * through the signed-in parent.
 *
 * Broken transactions (QPay certification): while an earlier payment for the same
 * student has no result, a new one is refused — a parent who closed the QPay page
 * must not pay twice. After 20 minutes the earlier payment is inquired first, and
 * only a confirmed outcome frees the student for another top-up.
 */
class StartPaymentAction
{
    /** QPay certification: a payment without a result blocks new ones for this long before it is inquired. */
    public const BROKEN_AFTER_MINUTES = 20;

    public function execute(Guardian $guardian, Account $student, float $amount, string $lang = 'En'): array
    {
        try {
            $settings = QPaySettings::current();
            if (! $settings->isReady()) {
                throw new Exception('Online top-up is not available right now. Please contact the school office.', 1);
            }

            $limits = StudentSettings::current();
            $amount = round($amount, 2);
            if ($amount < $limits->topupMin || $amount > $limits->topupMax) {
                throw new Exception('Enter an amount between '.currency($limits->topupMin).' and '.currency($limits->topupMax).'.', 1);
            }

            $this->guardAgainstBrokenPayment($student);

            $transaction = DB::transaction(fn () => QpayTransaction::create([
                'type' => QpayTransaction::TYPE_PAYMENT,
                'pun' => self::newPun(),
                'account_id' => $student->id,
                'guardian_id' => $guardian->id,
                'amount' => $amount,
                'currency_code' => QPayClient::CURRENCY_QAR,
                'lang' => $lang === 'Ar' ? 'Ar' : 'En',
                'status' => QpayTransaction::STATUS_PENDING,
                'request_date' => QPayClient::timestamp(),
            ]));

            $return['success'] = true;
            $return['message'] = 'Payment started';
            $return['data'] = $transaction;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function guardAgainstBrokenPayment(Account $student): void
    {
        $pending = QpayTransaction::where('account_id', $student->id)
            ->where('type', QpayTransaction::TYPE_PAYMENT)
            ->where('status', QpayTransaction::STATUS_PENDING)
            ->oldest('id')
            ->get();

        foreach ($pending as $transaction) {
            $brokenAt = $transaction->created_at->copy()->addMinutes(self::BROKEN_AFTER_MINUTES);

            if (now()->lt($brokenAt->copy()->addMinute())) {
                throw new Exception('A top-up started at '.$transaction->created_at->format('h:i A').' is still being confirmed. You can try again after '.$brokenAt->copy()->addMinute()->format('h:i A').'.', 1);
            }

            (new InquireAction())->execute($transaction);

            if ($transaction->refresh()->isPending()) {
                throw new Exception('We are still confirming an earlier top-up with QPay. Please try again in a few minutes.', 1);
            }
        }
    }

    /** 20 alphanumeric characters, unique across every school. */
    public static function newPun(): string
    {
        do {
            $pun = strtoupper(Str::random(20));
        } while (QpayTransaction::withoutGlobalScopes()->where('pun', $pun)->exists());

        return $pun;
    }
}
