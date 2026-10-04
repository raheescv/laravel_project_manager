<?php

namespace App\Support\Payment;

use App\Models\ApiLog;
use App\Models\QpayTransaction;
use App\Services\Payment\MpgsClient;
use App\Services\Payment\QPayApiLog;
use App\Services\Payment\QPayClient;
use Illuminate\Support\Collection;

/**
 * Turns a student card top-up (or refund) and its gateway log into a
 * plain-language account of what happened — the recharge report's sibling of
 * TapChargeExplanation.
 *
 * QPay answers in four-digit codes and MPGS in SHOUTED_ENUMS; neither tells the
 * office whether money moved, who stopped the payment or what to do next. Every
 * line here is read from the stored transaction and the api_logs rows, so the
 * explanation is evidence, never a guess: a missing field simply drops its line.
 */
final class QPayRechargeExplanation
{
    /** MPGS response.gatewayCode → what it means for the parent. */
    private const MPGS_GATEWAY_CODES = [
        'APPROVED' => 'approved by the bank',
        'APPROVED_AUTO' => 'approved by the bank',
        'APPROVED_PENDING_SETTLEMENT' => 'approved, waiting for settlement',
        'DECLINED' => 'declined by the card\'s bank',
        'INSUFFICIENT_FUNDS' => 'declined — not enough funds on the card',
        'EXPIRED_CARD' => 'declined — the card has expired',
        'DECLINED_CSC' => 'declined — the security code (CVV) was wrong',
        'DECLINED_AVS' => 'declined — the billing address did not match',
        'DECLINED_INVALID_PIN' => 'declined — wrong PIN',
        'DECLINED_DO_NOT_CONTACT' => 'declined by the bank (do not retry this card)',
        'BLOCKED' => 'blocked by the gateway\'s risk rules',
        'AUTHENTICATION_FAILED' => 'stopped — the cardholder failed the bank\'s 3-D Secure check',
        'AUTHENTICATION_IN_PROGRESS' => 'waiting for the cardholder to finish the bank\'s 3-D Secure check',
        'TIMED_OUT' => 'timed out waiting for the bank',
        'ACQUIRER_SYSTEM_ERROR' => 'failed — the bank\'s system had an error',
        'CANCELLED' => 'cancelled',
        'ABORTED' => 'abandoned before it was finished',
        'REFERRED' => 'referred — the bank wants the cardholder to call them',
        'NOT_SUPPORTED' => 'refused — this card type is not supported',
        'SUBMITTED' => 'submitted, waiting for the bank',
        'PENDING' => 'waiting for the bank',
        'UNSPECIFIED_FAILURE' => 'failed for an unspecified reason',
    ];

    /** MPGS authenticationStatus → the 3-D Secure outcome. */
    private const MPGS_AUTHENTICATION = [
        'AUTHENTICATION_SUCCESSFUL' => 'cardholder verified by their bank',
        'AUTHENTICATION_ATTEMPTED' => 'attempted (the bank did not take part)',
        'AUTHENTICATION_FAILED' => 'the cardholder failed verification',
        'AUTHENTICATION_REJECTED' => 'the bank rejected the verification',
        'AUTHENTICATION_UNAVAILABLE' => 'unavailable at the time',
        'AUTHENTICATION_NOT_IN_EFFECT' => 'not used for this payment',
        'AUTHENTICATION_EXEMPT' => 'not required (exempt)',
        'AUTHENTICATION_INITIATED' => 'started but not finished',
        'AUTHENTICATION_PENDING' => 'started but not finished',
        'AUTHENTICATION_UNSUCCESSFUL' => 'the cardholder did not complete verification',
    ];

    /**
     * @param  Collection<int, ApiLog>  $logs
     * @return array{tone: string, headline: string, summary: string, facts: list<string>, timeline: list<array{at: string, label: string, ok: bool}>, next: ?string}
     */
    public static function from(QpayTransaction $transaction, Collection $logs): array
    {
        $gateway = $transaction->gatewayLabel();
        $amount = currency($transaction->amount);
        $card = $transaction->cardLabel();
        $onCard = $card ? ' with '.$card : '';
        $parent = $transaction->guardian?->name ?: 'The parent';
        $student = $transaction->account?->name ?: 'the student';
        $reason = $transaction->failure_reason ?: trim((string) $transaction->gateway_status_message);

        [$tone, $headline, $summary, $next] = $transaction->type === QpayTransaction::TYPE_REFUND
            ? match ($transaction->status) {
                QpayTransaction::STATUS_SUCCESS => ['ok', 'Refund completed', $amount.' went back to the parent\'s card'.$onCard.' through '.$gateway.'.', 'Banks usually show the money back on the card within a few working days.'],
                QpayTransaction::STATUS_REFUND_PENDING => ['warn', 'Refund accepted, not finished yet', $gateway.' accepted the refund of '.$amount.' but has not completed it yet.', 'Nothing to do — '.$gateway.' completes it on its side.'],
                QpayTransaction::STATUS_FAILED => ['bad', 'Refund refused', $gateway.' did not refund '.$amount.($reason ? ' — "'.$reason.'"' : '').'.', 'No money went back to the parent. Check the reason, then try again or refund another way.'],
                default => ['warn', 'Refund '.strtolower($transaction->statusLabel()), $gateway.' has not given a final answer on this refund yet.', null],
            }
        : match ($transaction->status) {
            QpayTransaction::STATUS_SUCCESS => ['ok', 'Top-up successful', $parent.' paid '.$amount.$onCard.' through '.$gateway.', and it was added to '.$student.'\'s card.', null],
            QpayTransaction::STATUS_REFUNDED => ['info', 'Paid, then refunded', $parent.' paid '.$amount.$onCard.' through '.$gateway.'; it was later refunded in full and taken off '.$student.'\'s card.', null],
            QpayTransaction::STATUS_PENDING => ['warn', 'Waiting for '.$gateway.'\'s answer', $parent.' was sent to '.$gateway.'\'s payment page, but '.$gateway.' has not told us the result yet.', 'Press Check to ask '.$gateway.' now. Pending payments are also checked automatically every few minutes.'],
            QpayTransaction::STATUS_CANCELLED => ['warn', 'Parent cancelled on the payment page', $parent.' left '.$gateway.'\'s card page with Cancel (or it timed out) — '.$gateway.' holds no payment for it.', 'No money was taken. It is still checked for a while in case the same session is paid after all.'],
            QpayTransaction::STATUS_FAILED => ['bad', 'Payment failed — no money taken', $gateway.' ended the payment without taking money'.($reason ? ' — "'.$reason.'"' : '').'.', 'Nothing to refund. The parent can try again, with another card if it keeps failing.'],
            QpayTransaction::STATUS_REVIEW => ['bad', 'Money taken — needs a person to check', $gateway.' reports the money was taken, but it could not be added to the card as-is'.($reason ? ': '.$reason : '.'), 'Compare it with the '.$gateway.' merchant portal, then credit the card or refund the parent.'],
            QpayTransaction::STATUS_UNRESOLVED => ['warn', 'No final answer — released by the office', $reason ?: $gateway.' never gave a final answer, and the office released the payment so the card could be used again.', 'It is still checked automatically, and added to the card if '.$gateway.' finally confirms it was paid.'],
            default => ['warn', $transaction->statusLabel(), $gateway.' has not given a final answer yet.', null],
        };

        return [
            'tone' => $tone,
            'headline' => $headline,
            'summary' => $summary,
            'facts' => self::facts($transaction, $logs),
            'timeline' => self::timeline($transaction, $logs),
            'next' => $next,
        ];
    }

    /**
     * One gateway exchange in plain words: what it was, and what came back.
     *
     * @return array{title: string, hint: string, outcome: ?string, tone: string}
     */
    public static function exchange(ApiLog $log): array
    {
        $response = self::decoded($log->response) ?? [];
        $request = self::decoded($log->request) ?? [];

        [$title, $hint] = match ($log->service_name) {
            QPayApiLog::PAYMENT => ['Payment page', 'The signed payment form handed to the parent\'s browser, and the result QPay posted back'],
            QPayApiLog::RETURN => ['Result from QPay', 'QPay posted a result with no open payment form to attach it to'],
            QPayApiLog::INQUIRY => ['Status check', 'We asked QPay what happened to the payment'],
            QPayApiLog::REFUND => ['Refund request', 'We asked QPay to give the money back'],
            MpgsClient::LOG_CHECKOUT => ['Checkout session', 'We opened a card payment page on the Mastercard Gateway'],
            MpgsClient::LOG_RETRIEVE => ['Status check', 'We asked the Mastercard Gateway for the order'],
            MpgsClient::LOG_REFUND => ['Refund request', 'We asked the Mastercard Gateway to give the money back'],
            MpgsClient::LOG_NOTIFICATION => ['Gateway notification', 'The Mastercard Gateway told us the outcome by webhook'],
            default => [(string) $log->service_name, (string) $log->endpoint],
        };

        $outcome = str_starts_with((string) $log->service_name, 'MPGS')
            ? self::mpgsOutcome($log->service_name === MpgsClient::LOG_NOTIFICATION ? $request : $response)
            : self::qpayOutcome($response);

        return [
            'title' => $title,
            'hint' => $hint,
            'outcome' => $log->status === 'pending' ? 'No answer recorded — the parent never came back from the payment page.' : ($log->description ?: $outcome),
            'tone' => match ($log->status) {
                'success' => 'ok',
                'pending' => 'warn',
                default => 'bad',
            },
        ];
    }

    /**
     * @param  Collection<int, ApiLog>  $logs
     * @return list<string>
     */
    private static function facts(QpayTransaction $transaction, Collection $logs): array
    {
        $payload = (array) $transaction->payload;
        $facts = [];

        if ($transaction->gateway_status || $transaction->gateway_status_message) {
            $facts[] = $transaction->gatewayLabel().' answered: '.trim($transaction->gateway_status.' — '.$transaction->gateway_status_message, ' —').self::qpayCodeMeaning((string) $transaction->gateway_status).'.';
        }

        $facts[] = $transaction->isCreditCard()
            ? 'Paid by credit card on the Mastercard Gateway (MPGS).'
            : 'Paid by Qatar debit card on QPay.';

        if ($card = $transaction->cardLabel()) {
            $facts[] = 'Card: '.$card.($transaction->funding_method ? ' ('.strtolower($transaction->funding_method).')' : '').'.';
        }

        if ($code = $payload['gateway_code'] ?? null) {
            $facts[] = 'Bank result: '.(self::MPGS_GATEWAY_CODES[$code] ?? strtolower(str_replace('_', ' ', $code))).($payload['acquirer_message'] ?? null ? ' ("'.$payload['acquirer_message'].'")' : '').'.';
        }

        if ($auth = $payload['authentication_status'] ?? null) {
            $facts[] = '3-D Secure: '.(self::MPGS_AUTHENTICATION[$auth] ?? strtolower(str_replace('_', ' ', $auth))).'.';
        }

        if ($transaction->confirmation_id) {
            $facts[] = 'Confirmation / receipt no: '.$transaction->confirmation_id.'.';
        }

        if ($transaction->journal_id) {
            $facts[] = 'Booked in the accounts (journal #'.$transaction->journal_id.').';
        } elseif ($transaction->type === QpayTransaction::TYPE_PAYMENT && $transaction->status !== QpayTransaction::STATUS_SUCCESS) {
            $facts[] = 'Nothing was booked in the accounts and the card balance was not touched.';
        }

        if ($transaction->tampered_at) {
            $facts[] = 'The result the browser brought back failed the secure-hash check, so it was ignored and the status was confirmed by asking '.$transaction->gatewayLabel().' directly.';
        }

        $checks = $logs->filter(fn (ApiLog $log): bool => in_array($log->service_name, [QPayApiLog::INQUIRY, MpgsClient::LOG_RETRIEVE], true))->count();
        if ($checks) {
            $facts[] = 'Checked with '.$transaction->gatewayLabel().' '.$checks.' '.str('time')->plural($checks).($transaction->last_inquired_at ? ', last on '.systemDateTime($transaction->last_inquired_at) : '').'.';
        }

        if (! $logs->count()) {
            $facts[] = 'No gateway messages were logged for this payment (it may predate logging).';
        }

        return array_values(array_filter($facts));
    }

    /**
     * Start → each exchange that moved the payment on → the end. Repeated status
     * checks are folded into one line, since the scheduler can ask hundreds of times.
     *
     * @param  Collection<int, ApiLog>  $logs
     * @return list<array{at: string, label: string, ok: bool}>
     */
    private static function timeline(QpayTransaction $transaction, Collection $logs): array
    {
        $steps = [];
        if ($transaction->created_at) {
            $steps[] = ['at' => systemDateTime($transaction->created_at), 'label' => $transaction->type === QpayTransaction::TYPE_REFUND ? 'Refund started' : 'Top-up started', 'ok' => true];
        }

        $checks = collect();
        $flush = function () use (&$checks, &$steps): void {
            if ($checks->isEmpty()) {
                return;
            }
            $steps[] = [
                'at' => systemDateTime($checks->last()->created_at),
                'label' => $checks->count() === 1 ? 'Status check' : 'Status checked '.$checks->count().' times',
                'ok' => $checks->last()->status === 'success',
            ];
            $checks = collect();
        };

        foreach ($logs as $log) {
            if (in_array($log->service_name, [QPayApiLog::INQUIRY, MpgsClient::LOG_RETRIEVE], true)) {
                $checks->push($log);

                continue;
            }
            $flush();
            $steps[] = ['at' => systemDateTime($log->created_at), 'label' => self::exchange($log)['title'], 'ok' => $log->status === 'success'];
        }
        $flush();

        if ($transaction->completed_at) {
            $steps[] = ['at' => systemDateTime($transaction->completed_at), 'label' => $transaction->statusLabel(), 'ok' => in_array($transaction->status, [QpayTransaction::STATUS_SUCCESS, QpayTransaction::STATUS_REFUNDED], true)];
        }

        return $steps;
    }

    /**
     * A logged request/response as an array. The gateway clients store already
     * encoded JSON in api_logs' array-cast columns, so it reads back as a string
     * — sometimes encoded twice over — and is unwrapped until it is JSON again.
     *
     * @return array<mixed>|null
     */
    public static function decoded(mixed $value): ?array
    {
        for ($depth = 0; is_string($value) && $depth < 5; $depth++) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : null;
    }

    /** @param  array<string, mixed>  $values */
    private static function qpayOutcome(array $values): ?string
    {
        $code = (string) ($values['OriginalStatus'] ?? $values['Status'] ?? $values['Status_1'] ?? $values['EZConnectRequestStatus'] ?? '');
        $message = $values['OriginalStatusMessage'] ?? $values['StatusMessage'] ?? $values['StatusMessage_1'] ?? $values['EZConnectStatusMessage'] ?? null;

        if ($code === '' && ! $message) {
            return isset($values['http_status']) ? 'QPay answered HTTP '.$values['http_status'].'.' : null;
        }

        return trim($code.' — '.$message, ' —').self::qpayCodeMeaning($code);
    }

    /** @param  array<string, mixed>  $values */
    private static function mpgsOutcome(array $values): ?string
    {
        if ($error = data_get($values, 'error.explanation')) {
            return 'Error: '.$error;
        }

        $gatewayCode = data_get($values, 'response.gatewayCode') ?? data_get(MpgsClient::lastPayment($values), 'response.gatewayCode');
        $status = data_get($values, 'order.status') ?? data_get($values, 'status');

        $parts = array_filter([
            $status ? 'Order '.strtolower(str_replace('_', ' ', (string) $status)) : null,
            $gatewayCode ? (self::MPGS_GATEWAY_CODES[$gatewayCode] ?? strtolower(str_replace('_', ' ', (string) $gatewayCode))) : null,
            data_get($values, 'session.id') ? 'payment page opened' : null,
        ]);

        return $parts ? ucfirst(implode(' — ', $parts)).'.' : (data_get($values, 'result') ? 'Result: '.data_get($values, 'result').'.' : null);
    }

    /** The QPay codes this application acts on, in words. */
    private static function qpayCodeMeaning(string $code): string
    {
        return match ($code) {
            QPayClient::SUCCESS => ' (accepted)',
            QPayClient::REFUND_PENDING => ' (refund accepted, completes later)',
            QPayClient::NOT_FOUND => ' (QPay has no such payment — the parent never paid)',
            default => '',
        };
    }
}
