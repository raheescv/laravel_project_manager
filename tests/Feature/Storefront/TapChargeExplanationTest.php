<?php

use App\Support\Storefront\TapChargeExplanation;

/**
 * Staff see what happened to a Tap charge, not just Tap's one-word message.
 */
beforeEach(function (): void {
    config(['app.timezone' => 'UTC']);
});

function cancelledOnPageCharge(): array
{
    return [
        'id' => 'chg_LV06J1320262222s8N40110251',
        'post' => ['url' => 'https://example.test/api/v1/storefront/checkout/tap-webhook', 'status' => 'PENDING'],
        'amount' => 950,
        'source' => ['id' => 'src_all', 'type' => '', 'payment_type' => '', 'payment_method' => ''],
        'status' => 'CANCELLED',
        'redirect' => ['url' => 'https://sizerun.com/?checkout=abc', 'status' => 'SUCCESS'],
        'response' => ['code' => '302', 'message' => 'Cancelled'],
        'initiator' => 'CUSTOMER',
        'live_mode' => true,
        'activities' => [
            ['status' => 'CANCELLED', 'created' => 1790893350956, 'remarks' => 'charge - cancelled'],
            ['status' => 'INITIATED', 'created' => 1790893333251, 'remarks' => 'charge - created'],
        ],
        'transaction' => [
            'date' => ['created' => 1790893333251, 'completed' => 1790893350956],
            'expiry' => ['type' => 'MINUTE', 'period' => 30],
        ],
        'threeDSecure' => true,
    ];
}

it('explains a customer cancelling on the payment page before choosing a card', function (): void {
    $explained = TapChargeExplanation::from(cancelledOnPageCharge());

    expect($explained['headline'])->toBe('Customer cancelled the payment')
        ->and($explained['summary'])->toContain('pressed cancel after 18 seconds')
        ->and($explained['summary'])->toContain('without choosing a card')
        ->and($explained['next'])->toContain('nothing to refund')
        ->and(implode(' ', $explained['facts']))
        ->toContain('Tap response 302')
        ->toContain('initiator CUSTOMER')
        ->toContain('no card details were entered')
        ->toContain('bank was never contacted')
        ->toContain('sent back to the store (sizerun.com)')
        ->toContain('webhook to us shows PENDING')
        ->and($explained['timeline'])->toBe([
            ['at' => '01-10-2026 10:22:13 PM', 'label' => 'Created', 'status' => 'INITIATED'],
            ['at' => '01-10-2026 10:22:30 PM', 'label' => 'Cancelled', 'status' => 'CANCELLED'],
        ]);
});

it('names the card and the bank answer on a decline', function (): void {
    $charge = array_merge(cancelledOnPageCharge(), [
        'status' => 'DECLINED',
        'source' => ['id' => 'src_card', 'payment_method' => 'VISA'],
        'card' => ['first_six' => '411111', 'last_four' => '1111'],
        'acquirer' => ['response' => ['code' => '51', 'message' => 'Insufficient funds']],
        'security' => ['threeDSecure' => ['status' => 'Y']],
    ]);

    $explained = TapChargeExplanation::from($charge);

    expect($explained['headline'])->toBe('Card declined by the bank')
        ->and($explained['summary'])->toContain('Visa ending 1111')
        ->and($explained['summary'])->toContain('Insufficient funds (51)')
        ->and(implode(' ', $explained['facts']))->toContain('3-D Secure result: cardholder verified');
});

it('returns nothing when no charge was stored', function (): void {
    expect(TapChargeExplanation::from(null))->toBeNull()
        ->and(TapChargeExplanation::from([]))->toBeNull();
});
