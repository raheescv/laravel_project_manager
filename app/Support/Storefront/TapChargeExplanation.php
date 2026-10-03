<?php

namespace App\Support\Storefront;

use Carbon\CarbonImmutable;

/**
 * Turns a stored Tap charge into a plain-language account of what happened.
 *
 * Tap's own `response.message` is one word ("Cancelled", "Declined"), which tells
 * staff nothing about who stopped the payment, how far the customer got, whether
 * a bank was ever involved or whether any money moved. Everything here is read
 * from the charge itself — status, initiator, source, card, acquirer and 3-D
 * Secure results, the activity trail and the redirect/webhook delivery states —
 * so the explanation is evidence, never a guess. Every key is optional in Tap's
 * payload, so a missing one simply drops that line.
 */
final class TapChargeExplanation
{
    /**
     * @param  array<string, mixed>|null  $charge
     * @return array{tone: string, headline: string, summary: string, facts: list<string>, timeline: list<array{at: string, label: string, status: string}>, next: ?string}|null
     */
    public static function from(?array $charge): ?array
    {
        if (! $charge || ! isset($charge['status'])) {
            return null;
        }

        $status = strtoupper((string) $charge['status']);
        $byMerchant = strtoupper((string) data_get($charge, 'initiator')) === 'MERCHANT';
        $hasCard = (bool) (data_get($charge, 'card.last_four') || data_get($charge, 'card.first_six'));
        $method = data_get($charge, 'source.payment_method') ?: data_get($charge, 'card.brand') ?: data_get($charge, 'card.scheme');
        $methodChosen = $hasCard || filled($method) || ! in_array(data_get($charge, 'source.id'), [null, '', 'src_all'], true);
        $card = self::cardLabel($charge, $method);
        $bankSaid = self::bankResponse($charge);
        $seconds = self::secondsOnPage($charge);
        $onPage = $seconds !== null ? ' after '.self::duration($seconds).' on the Tap payment page' : '';

        [$tone, $headline, $summary, $next] = match ($status) {
            'CAPTURED' => ['ok', 'Payment captured', 'The customer paid'.($card ? ' with '.$card : '').' and Tap captured the money.', null],
            'AUTHORIZED' => ['warn', 'Payment authorised, not captured', 'The bank approved and is holding the amount'.($card ? ' on '.$card : '').', but it has not been captured yet.', 'The hold is released automatically if it is never captured.'],
            'INITIATED', 'IN_PROGRESS' => ['warn', 'Customer has not finished paying', 'Tap opened the payment page, but the customer has not completed or left it yet.', 'Check again in a few minutes — the page expires after '.self::expiry($charge).'.'],
            'CANCELLED' => $byMerchant
                ? ['bad', 'Cancelled by the shop', 'The charge was cancelled from the merchant side before any money was taken.', 'No money was taken — nothing to refund.']
                : ['bad', 'Customer cancelled the payment', $methodChosen
                    ? 'The customer started paying'.($card ? ' with '.$card : '').' but pressed cancel'.$onPage.' before the payment went through.'
                    : 'The customer opened the Tap payment page and pressed cancel'.$onPage.' without choosing a card or payment method.', 'No money was taken — nothing to refund. The customer can place the order again.'],
            'ABANDONED' => ['bad', 'Customer left the payment page', 'The customer left the Tap payment page'.($methodChosen ? ' part-way through paying' : ' without choosing a payment method').'; they closed the tab or went back.', 'No money was taken — nothing to refund.'],
            'TIMEDOUT' => ['bad', 'Payment page expired', 'The customer did not finish paying within '.self::expiry($charge).', so Tap closed the payment page.', 'No money was taken — nothing to refund.'],
            'DECLINED' => ['bad', 'Card declined by the bank', 'The customer\'s bank refused the payment'.($card ? ' on '.$card : '').($bankSaid ? ' — the bank answered "'.$bankSaid.'"' : '').'.', 'No money was taken. The customer should try another card or contact their bank.'],
            'RESTRICTED' => ['bad', 'Blocked by Tap\'s fraud checks', 'Tap\'s risk rules stopped the payment'.($card ? ' on '.$card : '').' before it reached the bank.', 'No money was taken. If the customer is genuine, raise it with Tap support.'],
            'FAILED' => ['bad', 'Payment failed during processing', 'The payment'.($card ? ' on '.$card : '').' could not be processed'.($bankSaid ? ' — "'.$bankSaid.'"' : '').'.', 'No money was taken. The customer can try again; if it keeps failing, raise it with Tap support.'],
            'VOID' => ['bad', 'Authorisation voided', 'The bank approved a hold'.($card ? ' on '.$card : '').', but it was voided before capture.', 'The held amount is released back to the customer by their bank.'],
            default => ['warn', 'Tap reported '.$status, 'Tap returned an unrecognised status for this charge.', 'Check the charge in the Tap dashboard.'],
        };

        return [
            'tone' => $tone,
            'headline' => $headline,
            'summary' => $summary,
            'facts' => self::facts($charge, $status, $card, $methodChosen, $bankSaid, $byMerchant),
            'timeline' => self::timeline($charge),
            'next' => $next,
        ];
    }

    /**
     * @param  array<string, mixed>  $charge
     * @return list<string>
     */
    private static function facts(array $charge, string $status, ?string $card, bool $methodChosen, ?string $bankSaid, bool $byMerchant): array
    {
        $facts = [];

        if ($code = data_get($charge, 'response.code')) {
            $facts[] = 'Tap response '.$code.' — '.(data_get($charge, 'response.message') ?: $status).'.';
        }

        $facts[] = match (true) {
            $byMerchant => 'Started by the merchant (initiator MERCHANT).',
            strtoupper((string) data_get($charge, 'initiator')) === 'CUSTOMER' => 'The action came from the customer on the payment page (initiator CUSTOMER).',
            default => null,
        };

        $facts[] = $card
            ? 'Payment method: '.$card.'.'
            : (! $methodChosen ? 'No card or wallet was chosen — Tap still shows the default "all methods" source, so no card details were entered.' : null);

        if ($threeDs = data_get($charge, 'security.threeDSecure.status')) {
            $facts[] = '3-D Secure result: '.self::threeDsLabel((string) $threeDs).'.';
        } elseif (data_get($charge, 'threeDSecure') && in_array($status, ['CANCELLED', 'ABANDONED', 'TIMEDOUT'], true) && ! $methodChosen) {
            $facts[] = '3-D Secure was required, but the customer never reached the bank\'s verification step.';
        }

        if ($bankSaid) {
            $facts[] = 'Bank / acquirer answered: "'.$bankSaid.'".';
        } elseif (! $methodChosen && $status !== 'CAPTURED') {
            $facts[] = 'The bank was never contacted, so nothing was charged or held on any card.';
        }

        if (data_get($charge, 'redirect.status') === 'SUCCESS') {
            $facts[] = 'The customer was sent back to the store'.(($host = parse_url((string) data_get($charge, 'redirect.url'), PHP_URL_HOST)) ? ' ('.$host.')' : '').'.';
        }

        $webhook = data_get($charge, 'post.status');
        if ($webhook && $webhook !== 'SUCCESS') {
            $facts[] = 'Tap\'s webhook to us shows '.$webhook.' — this status was picked up by checking Tap directly instead.';
        }

        if (data_get($charge, 'live_mode') === false) {
            $facts[] = 'This was a TEST-mode charge — no real money was involved.';
        }

        return array_values(array_filter($facts));
    }

    /**
     * @param  array<string, mixed>  $charge
     * @return list<array{at: string, label: string, status: string}>
     */
    private static function timeline(array $charge): array
    {
        $timezone = config('app.timezone');

        return collect(data_get($charge, 'activities', []))
            ->filter(fn ($activity): bool => is_array($activity) && isset($activity['created']))
            ->sortBy('created')
            ->map(fn (array $activity): array => [
                'at' => CarbonImmutable::createFromTimestampMs((int) $activity['created'])->setTimezone($timezone)->format('d-m-Y h:i:s A'),
                'label' => ucfirst(trim(str_replace('charge - ', '', (string) ($activity['remarks'] ?? strtolower((string) ($activity['status'] ?? '')))))),
                'status' => strtoupper((string) ($activity['status'] ?? '')),
            ])
            ->values()
            ->all();
    }

    /** @param  array<string, mixed>  $charge */
    private static function secondsOnPage(array $charge): ?int
    {
        $start = data_get($charge, 'transaction.date.created');
        $end = data_get($charge, 'transaction.date.completed');

        return is_numeric($start) && is_numeric($end) && $end >= $start ? (int) round(($end - $start) / 1000) : null;
    }

    /** @param  array<string, mixed>  $charge */
    private static function cardLabel(array $charge, ?string $method): ?string
    {
        $lastFour = data_get($charge, 'card.last_four');
        $name = $method ? ucwords(strtolower(str_replace('_', ' ', $method))) : null;

        return match (true) {
            $lastFour !== null => trim(($name ?: 'card').' ending '.$lastFour),
            $name !== null => $name,
            default => null,
        };
    }

    /** @param  array<string, mixed>  $charge */
    private static function bankResponse(array $charge): ?string
    {
        $message = data_get($charge, 'acquirer.response.message') ?: data_get($charge, 'gateway.response.message');
        $code = data_get($charge, 'acquirer.response.code') ?: data_get($charge, 'gateway.response.code');

        return $message ? $message.($code ? ' ('.$code.')' : '') : null;
    }

    /** @param  array<string, mixed>  $charge */
    private static function expiry(array $charge): string
    {
        $period = (int) data_get($charge, 'transaction.expiry.period', 30);
        $unit = strtolower((string) data_get($charge, 'transaction.expiry.type', 'MINUTE'));

        return $period.' '.$unit.($period === 1 ? '' : 's');
    }

    private static function duration(int $seconds): string
    {
        return $seconds < 60
            ? $seconds.' second'.($seconds === 1 ? '' : 's')
            : intdiv($seconds, 60).' min '.($seconds % 60).' s';
    }

    private static function threeDsLabel(string $status): string
    {
        return match (strtoupper($status)) {
            'Y' => 'cardholder verified',
            'N' => 'verification failed',
            'U' => 'verification unavailable',
            'A' => 'attempted (issuer not enrolled)',
            'R' => 'rejected by the issuer',
            default => $status,
        };
    }
}
