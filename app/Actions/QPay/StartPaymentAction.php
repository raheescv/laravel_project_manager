<?php

namespace App\Actions\QPay;

use App\Exceptions\TopupInProgressException;
use App\Models\Account;
use App\Models\Guardian;
use App\Models\QpayTransaction;
use App\Services\Payment\QPayClient;
use App\Support\Payment\MpgsSettings;
use App\Support\Payment\QPaySettings;
use App\Support\Student\StudentSettings;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Open an online payment to top up a student's card — by Qatar debit card
 * through QPay, or by credit card through the Mastercard Gateway ($gateway).
 *
 * Nothing is credited here: the row is the record of a payment the parent is
 * about to make on the gateway's page. The caller has already resolved the
 * student through the signed-in parent.
 *
 * Broken transactions (QPay certification): while an earlier payment for the same
 * student has no result, a new one is refused — a parent who closed the QPay page
 * must not pay twice. After 20 minutes the earlier payment is inquired first, and
 * only a confirmed outcome frees the student for another top-up.
 *
 * That block is bounded. An inquiry can come back with no answer at all — QPay
 * unreachable, a response that fails the hash check, a status that is neither
 * "paid" nor "not found" — and nothing about repeating it a day later makes it
 * more likely to answer. Left unbounded, one such payment would lock the student
 * out of their card for good, which is a worse outcome than the double payment
 * the block exists to prevent. See [BLOCK_EXPIRES_AFTER_HOURS].
 *
 * Only QPay payments block. The rule is QPay's certification, and a credit card
 * payment is always read back from the gateway (Retrieve Order), so one left
 * open on the card page is simply credited if it turns out to have been paid.
 */
class StartPaymentAction
{
    /** QPay certification: a payment without a result blocks new ones for this long before it is inquired. */
    public const BROKEN_AFTER_MINUTES = 20;

    /**
     * How long a payment QPay has never answered for may go on blocking the student.
     *
     * The row itself stays pending — qpay:inquire-pending keeps asking, and the card
     * is still credited if the answer finally says paid — but after this it no longer
     * stands in the way of a new top-up. A day is long enough that a payment still in
     * flight would have landed, and short enough that a parent is not left unable to
     * feed their child. The office can lift it sooner with ReleaseAction.
     */
    public const BLOCK_EXPIRES_AFTER_HOURS = 24;

    /** How long the portal asks the parent to wait after an inquiry that answered nothing. */
    public const RETRY_AFTER_INQUIRY_MINUTES = 2;

    public function execute(Guardian $guardian, Account $student, float $amount, string $lang = 'En', string $gateway = QpayTransaction::GATEWAY_QPAY): array
    {
        try {
            $creditCard = $gateway === QpayTransaction::GATEWAY_MPGS;
            if (! ($creditCard ? MpgsSettings::current() : QPaySettings::current())->isReady()) {
                throw new Exception($creditCard
                    ? 'Credit card top-up is not available right now. Please pay by debit card or contact the school office.'
                    : 'Online top-up is not available right now. Please contact the school office.', 1);
            }

            $limits = StudentSettings::current();
            $amount = round($amount, 2);
            if ($amount < $limits->topupMin || $amount > $limits->topupMax) {
                throw new Exception('Enter an amount between '.currency($limits->topupMin).' and '.currency($limits->topupMax).'.', 1);
            }

            $this->guardAgainstBrokenPayment($student);

            $transaction = DB::transaction(fn () => QpayTransaction::create([
                'type' => QpayTransaction::TYPE_PAYMENT,
                'gateway' => $creditCard ? QpayTransaction::GATEWAY_MPGS : QpayTransaction::GATEWAY_QPAY,
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
            // The caller can count the parent down to it rather than repeat the clock time.
            if ($th instanceof TopupInProgressException) {
                $return['retry_at'] = $th->retryAt->toIso8601String();
                $return['pun'] = $th->pun;
            }
        }

        return $return;
    }

    private function guardAgainstBrokenPayment(Account $student): void
    {
        $pending = QpayTransaction::where('account_id', $student->id)
            ->where('type', QpayTransaction::TYPE_PAYMENT)
            ->where('gateway', QpayTransaction::GATEWAY_QPAY)
            ->where('status', QpayTransaction::STATUS_PENDING)
            // Older than this and QPay is never going to answer; the payment is
            // left pending to be chased, but it stops holding the card hostage.
            ->where('created_at', '>', now()->subHours(self::BLOCK_EXPIRES_AFTER_HOURS))
            ->oldest('id')
            ->get();

        foreach ($pending as $transaction) {
            $retryAt = $transaction->created_at->copy()->addMinutes(self::BROKEN_AFTER_MINUTES)->addMinute();

            if (now()->lt($retryAt)) {
                throw new TopupInProgressException('A top-up started at '.$transaction->created_at->format('h:i A').' is still being confirmed. You can try again after '.$retryAt->format('h:i A').'.', $retryAt, $transaction->pun);
            }

            (new InquireAction())->execute($transaction);

            if ($transaction->refresh()->isPending()) {
                // QPay was asked and said nothing useful. Carry a moment to count
                // down to anyway: without one the portal leaves the Pay button live,
                // and the parent taps it into the same sentence over and over with
                // nothing telling them this has a way out. Never past the point the
                // block lifts by itself.
                $freeAt = $transaction->created_at->copy()->addHours(self::BLOCK_EXPIRES_AFTER_HOURS);
                $retryAt = now()->addMinutes(self::RETRY_AFTER_INQUIRY_MINUTES)->min($freeAt);

                throw new TopupInProgressException(
                    'We are still confirming an earlier top-up with QPay. Please try again in a few minutes — if it still will not go through, the school office can release it for you.',
                    $retryAt,
                    $transaction->pun,
                );
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
