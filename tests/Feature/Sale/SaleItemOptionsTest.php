<?php

use App\Actions\Product\Option\SyncAction;
use App\Actions\Sale\CreateAction;
use App\Actions\Sale\StockUpdateAction;
use App\Models\OptionGroup;
use App\Models\OptionGroupItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItemOption;
use App\Support\ProductOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PosWorld;

/**
 * Option groups on a sale line: a Paw-er Drink asks a base (Red Bull opens a
 * sugar choice) and a flavour, with optional paid add-ons. The picks are saved
 * as a snapshot, priced from the database, checked against each group's rule,
 * and Red Bull takes a can out of stock.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(price: 30);
    $this->drink = $this->world->product;
    $this->drink->update(['name' => 'Paw-er Drink']);

    $this->can = Product::create([
        'tenant_id' => $this->world->tenant->id, 'type' => 'product', 'name' => 'Red Bull 250ml', 'code' => 'RB'.Str::upper(Str::random(4)),
        'unit_id' => $this->drink->unit_id, 'department_id' => $this->drink->department_id, 'main_category_id' => $this->drink->main_category_id,
        'mrp' => 8, 'created_by' => $this->world->user->id, 'updated_by' => $this->world->user->id,
    ]);
    DB::table('inventories')->insert([
        'tenant_id' => $this->world->tenant->id, 'branch_id' => $this->world->branch->id, 'product_id' => $this->can->id, 'quantity' => 10,
        'batch' => (string) Str::uuid(), 'cost' => 4, 'barcode_prefix' => '', 'barcode_number' => (string) random_int(10000000, 99999999),
        'created_by' => $this->world->user->id, 'updated_by' => $this->world->user->id, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $group = function (string $name, int $min, ?int $max, array $items): OptionGroup {
        $group = OptionGroup::create(['name' => $name, 'min_select' => $min, 'max_select' => $max]);
        foreach ($items as $sort => $item) {
            OptionGroupItem::create(['option_group_id' => $group->id, 'sort' => $sort] + $item);
        }

        return $group->load('items');
    };
    $this->sugar = $group('Sugar', 1, 1, [['name' => 'Regular'], ['name' => 'Sugar-free', 'price' => 2]]);
    $this->base = $group('Drink base', 1, 1, [
        ['name' => 'Red Bull', 'product_id' => $this->can->id, 'quantity' => 1, 'next_group_id' => $this->sugar->id],
        ['name' => 'Soda'],
    ]);
    $this->flavour = $group('Flavour', 1, 1, [['name' => 'Lemongrass'], ['name' => 'Mango Passion']]);
    $this->addOns = $group('Add-ons', 0, 3, [['name' => 'Extra ice'], ['name' => 'Chia', 'price' => 3]]);
    (new SyncAction())->execute($this->drink->id, [
        ['option_group_id' => $this->base->id], ['option_group_id' => $this->flavour->id], ['option_group_id' => $this->addOns->id],
    ]);

    $this->pick = fn (OptionGroup $group, string $name, ?OptionGroupItem $parent = null): array => array_filter([
        'option_group_item_id' => $group->items->firstWhere('name', $name)->id,
        'parent_option_group_item_id' => $parent?->id,
    ]);

    $this->sell = function (array $lines, string $status = 'completed'): array {
        $inventoryId = DB::table('inventories')->where('product_id', $this->drink->id)->where('branch_id', $this->world->branch->id)->value('id');
        $total = array_sum(array_column($lines, 'unit_price'));

        return (new CreateAction())->execute([
            'branch_id' => $this->world->branch->id,
            'account_id' => $this->world->accounts['general_customer'],
            'date' => today()->toDateString(),
            'sale_type' => 'normal',
            'status' => $status,
            'gross_amount' => $total, 'item_discount' => 0, 'tax_amount' => 0, 'other_discount' => 0, 'freight' => 0, 'round_off' => 0, 'paid' => $total,
            'items' => array_map(fn (array $line) => [
                'inventory_id' => $inventoryId, 'product_id' => $this->drink->id, 'unit_id' => $this->drink->unit_id, 'employee_id' => $this->world->user->id,
                'quantity' => 1, 'conversion_factor' => 1, 'discount' => 0, 'tax' => 0,
            ] + $line, $lines),
            'payments' => [['payment_method_id' => $this->world->cashAccountId, 'amount' => $total, 'date' => today()->toDateString()]],
            'comboOffers' => [],
        ], $this->world->user->id);
    };
    $this->canStock = fn (): float => (float) DB::table('inventories')->where('product_id', $this->can->id)->value('quantity');
});

it('sends the till every group with its rule and the follow-up group inside the option that opens it', function (): void {
    $groups = ProductOptions::forProduct($this->drink);

    expect(array_column($groups, 'name'))->toBe(['Drink base', 'Flavour', 'Add-ons'])
        ->and($groups[0]['rule'])->toBe('Pick exactly 1')
        ->and($groups[2]['rule'])->toBe('Optional · up to 3')
        ->and($groups[0]['items'][0]['next']['name'])->toBe('Sugar')
        ->and($groups[0]['items'][0]['next']['items'][1]['price'])->toBe(2.0)
        ->and($groups[0]['items'][1]['next'])->toBeNull();
});

it('lets a variant without groups of its own ask its family head\'s', function (): void {
    $variant = Product::factory()->variantOf($this->drink, '500ml')->create();

    expect(array_column(ProductOptions::forProduct($variant), 'name'))->toBe(['Drink base', 'Flavour', 'Add-ons']);
});

it('saves the picks as a snapshot, allows their price on top of the MRP and uses a can of Red Bull', function (): void {
    $redBull = $this->base->items->firstWhere('name', 'Red Bull');
    $response = ($this->sell)([[
        'unit_price' => 35,
        'options' => [
            ($this->pick)($this->base, 'Red Bull'),
            ($this->pick)($this->sugar, 'Sugar-free', $redBull),
            ($this->pick)($this->flavour, 'Mango Passion'),
            ($this->pick)($this->addOns, 'Chia'),
        ],
    ]]);

    expect($response['success'])->toBeTrue($response['message']);
    $options = Sale::find($response['data']->id)->items->first()->options;
    expect($options->pluck('option_name')->all())->toBe(['Red Bull', 'Sugar-free', 'Mango Passion', 'Chia'])
        ->and($options->pluck('group_name')->all())->toBe(['Drink base', 'Sugar', 'Flavour', 'Add-ons'])
        ->and($options->sum('price'))->toBe(5.0)
        ->and($options->firstWhere('option_name', 'Sugar-free')->parent_id)->toBe($options->firstWhere('option_name', 'Red Bull')->id)
        ->and(($this->canStock)())->toBe(9.0);

    $this->flavour->items->firstWhere('name', 'Mango Passion')->update(['name' => 'Mango']);
    expect(SaleItemOption::where('option_name', 'Mango Passion')->exists())->toBeTrue();
});

it('gives the can back when the sale is cancelled', function (): void {
    $response = ($this->sell)([[
        'unit_price' => 30,
        'options' => [($this->pick)($this->base, 'Red Bull'), ($this->pick)($this->sugar, 'Regular', $this->base->items->firstWhere('name', 'Red Bull')), ($this->pick)($this->flavour, 'Lemongrass')],
    ]]);
    expect(($this->canStock)())->toBe(9.0);

    (new StockUpdateAction())->execute(Sale::find($response['data']->id), $this->world->user->id, 'cancel');

    expect(($this->canStock)())->toBe(10.0);
});

it('refuses a line without a required pick, an option of another product and too many add-ons', function (): void {
    $missing = ($this->sell)([['unit_price' => 30, 'options' => [($this->pick)($this->base, 'Soda')]]]);
    expect($missing['success'])->toBeFalse()->and($missing['message'])->toContain('pick a Flavour');

    $sugarless = ($this->sell)([['unit_price' => 30, 'options' => [($this->pick)($this->base, 'Red Bull'), ($this->pick)($this->flavour, 'Lemongrass')]]]);
    expect($sugarless['message'])->toContain('pick a Sugar');

    $stray = ($this->sell)([['unit_price' => 30, 'options' => [($this->pick)($this->sugar, 'Regular'), ($this->pick)($this->base, 'Soda'), ($this->pick)($this->flavour, 'Lemongrass')]]]);
    expect($stray['message'])->toContain('no longer offered');

    $this->addOns->update(['max_select' => 1]);
    $tooMany = ($this->sell)([['unit_price' => 35, 'options' => [($this->pick)($this->base, 'Soda'), ($this->pick)($this->flavour, 'Lemongrass'), ($this->pick)($this->addOns, 'Chia'), ($this->pick)($this->addOns, 'Extra ice')]]]);
    expect($tooMany['message'])->toContain('no more than 1 Add-ons');
});

it('refuses a price above the MRP plus the picked options', function (): void {
    $response = ($this->sell)([['unit_price' => 33, 'options' => [($this->pick)($this->base, 'Soda'), ($this->pick)($this->flavour, 'Lemongrass'), ($this->pick)($this->addOns, 'Chia')]]]);
    expect($response['success'])->toBeTrue($response['message']);

    $over = ($this->sell)([['unit_price' => 31, 'options' => [($this->pick)($this->base, 'Soda'), ($this->pick)($this->flavour, 'Lemongrass')]]]);
    expect($over['success'])->toBeFalse()->and($over['message'])->toContain('greater than MRP');
});

it('keeps two lines of one drink with different picks apart', function (): void {
    $response = ($this->sell)([
        ['unit_price' => 30, 'options' => [($this->pick)($this->base, 'Soda'), ($this->pick)($this->flavour, 'Lemongrass')]],
        ['unit_price' => 30, 'options' => [($this->pick)($this->base, 'Soda'), ($this->pick)($this->flavour, 'Mango Passion')]],
    ]);

    expect($response['success'])->toBeTrue($response['message'])
        ->and(Sale::find($response['data']->id)->items)->toHaveCount(2);
});

it('still sells the drink from an older till that sends no options', function (): void {
    $response = ($this->sell)([['unit_price' => 30]]);

    expect($response['success'])->toBeTrue($response['message'])
        ->and(SaleItemOption::count())->toBe(0);
});

it('prints the picks under the line on the receipt', function (): void {
    \App\Models\Configuration::updateOrCreate(['key' => 'print_item_label'], ['value' => 'product']);
    $response = ($this->sell)([[
        'unit_price' => 33,
        'options' => [($this->pick)($this->base, 'Soda'), ($this->pick)($this->flavour, 'Mango Passion'), ($this->pick)($this->addOns, 'Chia')],
    ]]);

    $html = (new \App\Helpers\SaleHelper())->saleInvoice($response['data']->id);

    expect($html)->toContain('+ Soda')->toContain('+ Mango Passion')->toContain('+ Chia (');
});

it('hands the POS grid each card\'s option groups', function (): void {
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
    DB::table('categories')->where('id', $this->drink->main_category_id)->update(['sale_visibility_flag' => true]);
    $this->drink->update(['is_selling' => true]);

    $card = collect($this->getJson($this->world->url('products'))->assertOk()->json())->firstWhere('name', 'Paw-er Drink');

    expect(array_column($card['option_groups'], 'name'))->toBe(['Drink base', 'Flavour', 'Add-ons']);
});

it('takes the picks from the mobile app and lists the groups on its catalogue', function (): void {
    \Laravel\Sanctum\Sanctum::actingAs($this->world->user);

    $rows = collect($this->getJson($this->world->url('/api/v1/products?in_stock_only=0&search=Paw-er'))->assertSuccessful()->json('data.data'));
    expect(array_column($rows->firstWhere('id', $this->drink->id)['option_groups'], 'name'))->toBe(['Drink base', 'Flavour', 'Add-ons']);

    $soda = $this->base->items->firstWhere('name', 'Soda')->id;
    $mango = $this->flavour->items->firstWhere('name', 'Mango Passion')->id;
    $payload = $this->world->salePayload([
        'items' => [['productId' => $this->drink->id, 'quantity' => 1, 'unitPrice' => 30, 'discount' => 0,
            'options' => [['optionGroupItemId' => $soda], ['optionGroupItemId' => $mango]]]],
        'totalPayment' => 30,
    ]);
    $saleId = $this->postJson($this->world->url('/api/v1/sale'), $payload)->assertSuccessful()->json('data.id');

    expect(Sale::find($saleId)->items->first()->options->pluck('option_name')->all())->toBe(['Soda', 'Mango Passion']);

    $payload['items'][0]['options'] = [['optionGroupItemId' => $soda]];
    $this->postJson($this->world->url('/api/v1/sale'), $payload)->assertStatus(422);
});
