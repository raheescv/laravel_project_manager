<?php

use App\Livewire\Sale\OnlinePayments;
use App\Models\Configuration;
use App\Models\StorefrontCheckout;
use App\Support\Storefront\TapSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * /sale/online-payments — every storefront checkout through Tap. The rows the
 * sales list can't show (failed, pending, captured-but-unrecorded) are the point.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'name' => 'sale.online payments', 'guard_name' => 'web',
    ]));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
});

function onlinePayment(PosWorld $world, array $overrides = []): StorefrontCheckout
{
    return StorefrontCheckout::create([
        'tenant_id' => $world->tenant->id,
        'reference' => Str::random(32),
        'branch_id' => $world->branch->id,
        'fulfilment' => 'pickup',
        'customer_name' => 'Aisha Khan',
        'customer_mobile' => '55123456',
        'customer_email' => 'aisha@example.com',
        'items' => [],
        'amount' => 100,
        'currency' => 'QAR',
        'gateway_charge_id' => 'chg_'.Str::random(10),
        'status' => StorefrontCheckout::STATUS_PAID,
        ...$overrides,
    ]);
}

it('opens from the sale menu for staff with the permission', function (): void {
    $this->get(route('sale::online-payments'))->assertOk()->assertSee('Online Payments');
});

it('is forbidden without the permission', function (): void {
    $this->world->user->revokePermissionTo('sale.online payments');

    $this->get(route('sale::online-payments'))->assertForbidden();
});

it('lists every outcome and counts captured money including review', function (): void {
    onlinePayment($this->world, ['amount' => 250]);
    onlinePayment($this->world, ['status' => StorefrontCheckout::STATUS_REVIEW, 'amount' => 80, 'failure_reason' => 'Out of stock']);
    onlinePayment($this->world, ['status' => StorefrontCheckout::STATUS_FAILED, 'customer_name' => 'Omar Ali', 'failure_reason' => 'Declined']);
    onlinePayment($this->world, [
        'status' => StorefrontCheckout::STATUS_PENDING,
        'fulfilment' => 'delivery',
        'zone_number' => '56', 'street_number' => '340', 'building_number' => '12', 'city' => 'Doha',
        'latitude' => 25.2854473, 'longitude' => 51.5310398,
    ]);

    Livewire::test(OnlinePayments::class)
        ->assertViewHas('totals', fn (array $totals) => $totals['collected'] === 330.0
            && $totals['paid'] === 1 && $totals['review'] === 1 && $totals['failed'] === 1 && $totals['pending'] === 1)
        ->assertSee('Out of stock')
        ->assertSee('Z 56 · St 340 · Bldg 12')
        ->assertSee('maps?q=25.2854473,51.5310398', false);
});

it('filters by status, fulfilment and search', function (): void {
    onlinePayment($this->world);
    onlinePayment($this->world, ['status' => StorefrontCheckout::STATUS_FAILED, 'customer_name' => 'Omar Ali']);
    onlinePayment($this->world, ['fulfilment' => 'delivery', 'customer_name' => 'Sara Noor']);

    $names = fn ($component) => $component->viewData('rows')->pluck('customer_name')->sort()->values()->all();

    expect($names(Livewire::test(OnlinePayments::class)->set('status', 'failed')))->toBe(['Omar Ali'])
        ->and($names(Livewire::test(OnlinePayments::class)->set('fulfilment', 'delivery')))->toBe(['Sara Noor'])
        ->and($names(Livewire::test(OnlinePayments::class)->set('search', 'Omar')))->toBe(['Omar Ali']);
});

it('asks Tap about a pending payment from its row', function (): void {
    Configuration::create(['tenant_id' => $this->world->tenant->id, 'key' => TapSettings::SECRET_KEY, 'value' => TapSettings::encryptSecret('sk_test_fake')]);
    $checkout = onlinePayment($this->world, ['status' => StorefrontCheckout::STATUS_PENDING, 'gateway_charge_id' => 'chg_TS_pending']);
    Http::fake(['*' => Http::response([
        'id' => 'chg_TS_pending',
        'status' => 'DECLINED',
        'amount' => 100,
        'currency' => 'QAR',
        'response' => ['code' => '507', 'message' => 'Declined'],
    ])]);

    Livewire::test(OnlinePayments::class)->call('check', $checkout->id)->assertDispatched('success');

    expect($checkout->fresh()->status)->toBe(StorefrontCheckout::STATUS_FAILED)
        ->and($checkout->fresh()->failure_reason)->toBe('Declined');
});

it('filters by status from the summary cards and clears on a second press', function (): void {
    onlinePayment($this->world, ['customer_name' => 'Paid Person']);
    onlinePayment($this->world, ['status' => StorefrontCheckout::STATUS_REFUNDED, 'customer_name' => 'Refunded Person']);

    $names = fn ($component) => $component->viewData('rows')->pluck('customer_name')->sort()->values()->all();

    $component = Livewire::test(OnlinePayments::class)->call('filterStatus', 'refunded');
    expect($component->get('status'))->toBe('refunded')
        ->and($names($component))->toBe(['Refunded Person']);

    $component->call('filterStatus', 'refunded');
    expect($component->get('status'))->toBe('')
        ->and($names($component))->toBe(['Paid Person', 'Refunded Person']);

    expect(Livewire::test(OnlinePayments::class)->call('filterStatus', 'bogus')->get('status'))->toBe('');
});
