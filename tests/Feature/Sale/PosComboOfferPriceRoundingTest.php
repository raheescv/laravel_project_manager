<?php

use App\Actions\Sale\CreateAction;
use App\Http\Middleware\RequireOpenDaySession;
use App\Models\ComboOffer;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * Re-opening a saved sale in the POS rebuilds each combo line's price as
 * unit_price − discount. Done in raw floats that leaks binary noise into the
 * price field (150 − 114.24 = 35.760000000000005), so it must be rounded.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(price: 150);
    $this->world->user->givePermissionTo(Permission::firstOrCreate([
        'tenant_id' => $this->world->tenant->id, 'name' => 'sale.create', 'guard_name' => 'web',
    ]));
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
    $this->withoutMiddleware(RequireOpenDaySession::class);
});

it('hands the POS combo prices rounded to two decimals', function (): void {
    $world = $this->world;
    $inventoryId = DB::table('inventories')
        ->where('product_id', $world->product->id)
        ->where('branch_id', $world->branch->id)
        ->value('id');

    $comboOffer = ComboOffer::create([
        'tenant_id' => $world->tenant->id,
        'name' => 'combo 3',
        'count' => 1,
        'amount' => 35.76,
        'status' => 'active',
    ]);

    $created = (new CreateAction())->execute([
        'branch_id' => $world->branch->id,
        'account_id' => $world->accounts['general_customer'],
        'date' => today()->toDateString(),
        'sale_type' => 'normal',
        'status' => 'completed',
        'gross_amount' => 150,
        'item_discount' => 114.24,
        'tax_amount' => 0,
        'other_discount' => 0,
        'freight' => 0,
        'round_off' => 0,
        'paid' => 35.76,
        'items' => [[
            'inventory_id' => $inventoryId,
            'product_id' => $world->product->id,
            'unit_id' => $world->product->unit_id,
            'employee_id' => $world->user->id,
            'unit_price' => 150,
            'quantity' => 1,
            'conversion_factor' => 1,
            'discount' => 114.24,
            'tax' => 0,
        ]],
        'payments' => [[
            'payment_method_id' => $world->cashAccountId,
            'amount' => 35.76,
            'date' => today()->toDateString(),
        ]],
        'comboOffers' => [[
            'combo_offer_id' => $comboOffer->id,
            'amount' => 35.76,
            'items' => [['inventory_id' => $inventoryId, 'employee_id' => $world->user->id]],
        ]],
    ], $world->user->id);

    expect($created['success'])->toBeTrue($created['message'] ?? '');

    $this->get($world->url(route('sale::pos', ['id' => $created['data']->id], absolute: false)))
        ->assertInertia(fn ($page) => $page
            ->where('saleData.comboOffers.0.items.0.combo_offer_price', 35.76)
            ->where('saleData.comboOffers.0.items.0.discount', 114.24)
            ->where('saleData.items', fn ($items) => collect($items)->every(
                fn ($item) => $item['combo_offer_price'] === 35.76 && $item['discount'] === 114.24
            )));
});
