<?php

use App\Livewire\Settings\SaleConfiguration;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\SaleDaySession;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Sale Settings → POS Without Day Session (`pos_skip_day_session`): the POS
 * opens with no day session, shows a date picker, and each sale keeps the
 * picked date instead of being bound to a session.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'name' => 'sale.create', 'guard_name' => 'web',
    ]));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->skipDaySession = fn (bool $on = true) => Configuration::updateOrCreate(
        ['key' => 'pos_skip_day_session'],
        ['value' => $on ? 'yes' : 'no'],
    );

    $this->openSession = fn () => SaleDaySession::create([
        'tenant_id' => $this->world->tenant->id,
        'branch_id' => $this->world->branch->id,
        'opened_by' => $this->world->user->id,
        'opened_at' => now()->subHour(),
        'status' => 'open',
    ]);

    $this->submitPayload = function (array $overrides = []): array {
        $world = $this->world;
        $inventoryId = DB::table('inventories')
            ->where('product_id', $world->product->id)
            ->where('branch_id', $world->branch->id)
            ->value('id');
        $price = (float) $world->product->mrp;

        return array_merge([
            'id' => null,
            'date' => today()->toDateString(),
            'employee_id' => $world->user->id,
            'sale_type' => 'normal',
            'account_id' => $world->accounts['general_customer'],
            'customer_mobile' => '',
            'other_discount' => 0,
            'round_off' => 0,
            'gross_amount' => $price,
            'item_discount' => 0,
            'tax_amount' => 0,
            'total' => $price,
            'grand_total' => $price,
            'items' => [
                'line-1' => [
                    'inventory_id' => $inventoryId,
                    'product_id' => $world->product->id,
                    'unit_id' => $world->product->unit_id,
                    'employee_id' => $world->user->id,
                    'unit_price' => $price,
                    'quantity' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
            'comboOffers' => [],
            'payment_method' => $world->cashAccountId,
            'custom_payment_data' => ['payments' => []],
            'send_to_whatsapp' => false,
            'notes' => null,
            'status' => 'completed',
        ], $overrides);
    };
});

it('sends the POS to day management when no session is open and the setting is off', function (): void {
    $this->get($this->world->url(route('sale::pos', absolute: false)))
        ->assertRedirect(route('sale::day-management'));
});

it('opens the POS with a date picker and no session when the setting is on', function (): void {
    ($this->skipDaySession)();

    $this->get($this->world->url(route('sale::pos', absolute: false)))
        ->assertInertia(fn ($page) => $page
            ->where('dateEditable', true)
            ->where('today', today()->toDateString())
            ->where('daySession', null)
            ->where('saleData.date', today()->toDateString()));
});

it('ignores an open session on a new ticket when the setting is on', function (): void {
    ($this->skipDaySession)();
    ($this->openSession)();

    $this->get($this->world->url(route('sale::pos', absolute: false)))
        ->assertInertia(fn ($page) => $page->where('dateEditable', true)->where('daySession', null));
});

it('starts the ticket on a backdated date carried in the url', function (?string $requested, string $expected): void {
    ($this->skipDaySession)();

    $this->get($this->world->url(route('sale::pos', ['date' => $requested], absolute: false)))
        ->assertInertia(fn ($page) => $page->where('saleData.date', $expected));
})->with([
    'yesterday' => fn () => [today()->subDay()->toDateString(), today()->subDay()->toDateString()],
    'future falls back to today' => fn () => [today()->addDay()->toDateString(), today()->toDateString()],
    'garbage falls back to today' => fn () => ['not-a-date', today()->toDateString()],
]);

it('keeps the session chip and no picker when the setting is off', function (): void {
    $session = ($this->openSession)();

    $this->get($this->world->url(route('sale::pos', absolute: false)))
        ->assertInertia(fn ($page) => $page->where('dateEditable', false)->where('daySession.id', $session->id));
});

it('dates the sale from the picker and keeps it out of the open session', function (): void {
    ($this->skipDaySession)();
    ($this->openSession)();
    $yesterday = today()->subDay()->toDateString();

    $response = $this->postJson($this->world->url(route('api.pos.submit', absolute: false)), ($this->submitPayload)(['date' => $yesterday]))
        ->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->find($response->json('sale_id'));
    expect($sale->date)->toBe($yesterday)
        ->and($sale->sale_day_session_id)->toBeNull()
        ->and($sale->payments()->value('date'))->toBe($yesterday)
        ->and($response->json('redirect'))->toContain('date='.$yesterday);
});

it('does not carry today into the next ticket url', function (): void {
    ($this->skipDaySession)();

    $response = $this->postJson($this->world->url(route('api.pos.submit', absolute: false)), ($this->submitPayload)())
        ->assertSuccessful();

    expect($response->json('redirect'))->not->toContain('date=');
});

it('refuses a future sale date when the setting is on', function (): void {
    ($this->skipDaySession)();

    $this->postJson($this->world->url(route('api.pos.submit', absolute: false)), ($this->submitPayload)(['date' => today()->addDay()->toDateString()]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date');
});

it('still binds the sale to the open session when the setting is off', function (): void {
    $session = ($this->openSession)();

    $response = $this->postJson($this->world->url(route('api.pos.submit', absolute: false)), ($this->submitPayload)(['date' => today()->subDays(5)->toDateString()]))
        ->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->find($response->json('sale_id'));
    expect($sale->sale_day_session_id)->toBe($session->id)
        ->and($sale->date)->toBe($session->opened_at->toDateString());
});

it('saves the setting from Sale Configuration', function (): void {
    Livewire::test(SaleConfiguration::class)
        ->assertSet('pos_skip_day_session', 'no')
        ->set('pos_skip_day_session', 'yes')
        ->call('save');

    expect(posSkipsDaySession())->toBeTrue();
});
