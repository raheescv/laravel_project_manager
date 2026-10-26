<?php

use App\Http\Controllers\SaleController;
use App\Livewire\Settings\Configurations;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\Support\PosWorld;

/**
 * Settings → System Configurations → POS Payment Buttons: up to three quick
 * buttons on the POS checkout (web and app) ahead of Credit and Custom. The
 * first two fall back to Cash and Card; the third shows only once it is set.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Configuration::where('key', 'pos_payment_buttons')->delete();
    $this->world->user->givePermissionTo(config('permission.models.permission')::firstOrCreate(['name' => 'configuration.settings', 'guard_name' => 'web']));
});

/** @return array<string, mixed> */
function paymentButtonsPosPageProps(): array
{
    $request = Request::create('/sale/pos', 'GET', server: ['HTTP_X_INERTIA' => 'true']);
    app()->instance('request', $request);

    return app(SaleController::class)->posPage()->toResponse($request)->getData(true)['props'];
}

it('defaults to Cash then Card on the POS and in the app', function (): void {
    $expected = [
        ['id' => $this->world->cashAccountId, 'name' => 'Cash', 'slug' => 'cash', 'icon' => 'money'],
        ['id' => $this->world->accounts['card'], 'name' => 'Card', 'slug' => 'card', 'icon' => 'credit-card'],
    ];

    expect(posPaymentButtons())->toBe($expected);

    $this->actingAs($this->world->user);
    $props = paymentButtonsPosPageProps();
    expect($props['posPaymentButtons'])->toBe($expected)
        ->and($props['defaultPaymentMethodId'])->toBe($this->world->cashAccountId)
        ->and($props['saleData']['payment_method'])->toBe($this->world->cashAccountId);

    Sanctum::actingAs($this->world->user);
    $this->getJson($this->world->url('/api/v1/settings/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.payment_buttons', $expected);
});

it('saves three buttons from settings and preselects the first', function (): void {
    $bankId = $this->world->addPaymentMethod('Bank Transfer');
    $this->actingAs($this->world->user);

    Livewire::test(Configurations::class)
        ->assertSet('pos_payment_buttons', [$this->world->cashAccountId, $this->world->accounts['card'], null])
        ->set('pos_payment_buttons', [$bankId, $this->world->cashAccountId, $this->world->accounts['card']])
        ->call('save');

    expect(array_column(posPaymentButtons(), 'id'))->toBe([$bankId, $this->world->cashAccountId, $this->world->accounts['card']])
        ->and(paymentButtonsPosPageProps()['defaultPaymentMethodId'])->toBe($bankId);
});

it('shows a third button only once it is configured', function (): void {
    $benefitId = $this->world->addPaymentMethod('Benefit Pay');

    expect(posPaymentButtons())->toHaveCount(2);

    Configuration::updateOrCreate(['key' => 'pos_payment_buttons'], ['value' => json_encode([null, null, $benefitId])]);

    expect(array_column(posPaymentButtons(), 'name'))->toBe(['Cash', 'Card', 'Benefit Pay']);
});

it('drops a button that is not a payment method when saving', function (): void {
    $this->actingAs($this->world->user);

    Livewire::test(Configurations::class)
        ->set('pos_payment_buttons', [$this->world->cashAccountId, $this->world->accounts['card'], $this->world->accounts['sale']])
        ->call('save');

    expect(json_decode(Configuration::where('key', 'pos_payment_buttons')->value('value'), true))
        ->toBe([$this->world->cashAccountId, $this->world->accounts['card'], null]);
});

it('sends the picked icon, or the Auto one, and drops an icon not on the list', function (): void {
    $fawranId = $this->world->addPaymentMethod('Fawran');
    $this->actingAs($this->world->user);

    $component = Livewire::test(Configurations::class)
        ->set('pos_payment_buttons', [$this->world->cashAccountId, $this->world->accounts['card'], $fawranId])
        ->set('pos_payment_button_icons', [null, 'rocket', 'bolt']);
    expect($component->instance()->buttonIcon(0))->toBe('money')
        ->and($component->instance()->buttonIcon(1))->toBe('credit-card')
        ->and($component->instance()->buttonIcon(2))->toBe('bolt');
    $component->call('save');

    expect(json_decode(Configuration::where('key', 'pos_payment_button_icons')->value('value'), true))->toBe([null, null, 'bolt'])
        ->and(array_column(posPaymentButtons(), 'icon'))->toBe(['money', 'credit-card', 'bolt']);

    Sanctum::actingAs($this->world->user);
    $this->getJson($this->world->url('/api/v1/settings/sale'))
        ->assertSuccessful()
        ->assertJsonPath('data.payment_buttons.2.icon', 'bolt');
});
