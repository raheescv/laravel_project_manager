<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Support\PosWorld;

/**
 * The mobile Reports screen's By Category breakdown: the item-wise figures
 * rolled up to each product's main category, ranked by amount or quantity.
 *
 * @see App\Actions\V1\Report\GetAction::categoryWise()
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();

    foreach (['report.sale item', 'report.sales overview'] as $name) {
        $this->world->user->givePermissionTo(Permission::firstOrCreate([
            'tenant_id' => $this->world->tenant->id,
            'name' => $name,
            'guard_name' => 'web',
        ]));
    }

    $this->shoesId = DB::table('categories')->insertGetId([
        'tenant_id' => $this->world->tenant->id, 'name' => 'Shoes',
    ]);

    // Two more products, both under Shoes and stocked at the main branch.
    $stocked = function (string $name, float $price): Product {
        $product = Product::create([
            'tenant_id' => $this->world->tenant->id,
            'type' => 'product',
            'name' => $name.' '.Str::random(6),
            'code' => 'P'.Str::upper(Str::random(6)),
            'unit_id' => $this->world->product->unit_id,
            'department_id' => $this->world->product->department_id,
            'main_category_id' => $this->shoesId,
            'mrp' => $price,
            'tax' => 0,
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
        ]);
        DB::table('inventories')->insert([
            'tenant_id' => $this->world->tenant->id,
            'branch_id' => $this->world->branch->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'batch' => (string) Str::uuid(),
            'cost' => 5,
            'barcode_prefix' => '',
            'barcode_number' => (string) random_int(10000000, 99999999),
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $product;
    };

    $sneaker = $stocked('Sneaker', 30);
    $sandal = $stocked('Sandal', 10);

    // General: 2 × 50 = 100. Shoes: 1 × 30 + 3 × 10 = 60 over 4 units.
    $line = fn (Product $product, float $quantity) => [
        'productId' => $product->id,
        'quantity' => $quantity,
        'unitPrice' => (float) $product->mrp,
        'discount' => 0,
    ];
    $this->sell = function (User $user, array $items): void {
        Sanctum::actingAs($user);
        $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
            'clientUuid' => (string) Str::uuid(),
            'items' => $items,
            'totalPayment' => collect($items)->sum(fn ($i) => $i['quantity'] * $i['unitPrice']),
        ]))->assertSuccessful();
    };
    ($this->sell)($this->world->user, [$line($this->world->product, 2), $line($sneaker, 1), $line($sandal, 3)]);

    Sanctum::actingAs($this->world->user);

    $this->report = fn (array $query) => $this->getJson(
        $this->world->url('/api/v1/admin/reports?'.http_build_query(['type' => 'categorywise', ...$query]))
    )->assertSuccessful()->json('data');
});

it('totals each main category and ranks by amount', function (): void {
    $data = ($this->report)([]);

    expect($data['rows'])->toHaveCount(2)
        ->and($data['rows'][0])->toMatchArray([
            'category_name' => 'General',
            'products_count' => 1,
            'bills_count' => 1,
        ])
        ->and($data['rows'][0]['quantity'])->toEqual(2.0)
        ->and($data['rows'][0]['total'])->toEqual(100.0)
        ->and($data['rows'][1])->toMatchArray([
            'category_id' => (string) $this->shoesId,
            'category_name' => 'Shoes',
            'products_count' => 2,
        ])
        ->and($data['rows'][1]['quantity'])->toEqual(4.0)
        ->and($data['rows'][1]['total'])->toEqual(60.0)
        ->and($data['summary']['categories'])->toBe(2)
        ->and($data['summary']['total_quantity'])->toEqual(6.0)
        ->and($data['summary']['total_amount'])->toEqual(160.0)
        ->and($data['pagination']['total'])->toBe(2);
});

it('ranks by quantity when asked', function (): void {
    $data = ($this->report)(['sort' => 'quantity']);

    expect(array_column($data['rows'], 'category_name'))->toBe(['Shoes', 'General']);
});

it('applies the item type filter', function (): void {
    $data = ($this->report)(['product_type' => 'service']);

    expect($data['rows'])->toBeEmpty()
        ->and($data['summary']['total_amount'])->toEqual(0.0);
});

it('keeps sales whose category was deleted, as Uncategorised', function (): void {
    DB::table('categories')->where('id', $this->shoesId)->delete();

    $data = ($this->report)([]);
    $orphan = collect($data['rows'])->firstWhere('category_name', 'Uncategorised');

    expect($orphan)->not->toBeNull()
        ->and($orphan['category_id'])->toBeNull()
        ->and($orphan['total'])->toEqual(60.0)
        ->and($data['summary']['total_amount'])->toEqual(160.0);
});

it('follows the requested branch', function (): void {
    $second = $this->world->addBranch();

    expect(($this->report)(['branch_id' => $second->id])['rows'])->toBeEmpty()
        ->and(($this->report)(['branch_id' => $this->world->branch->id])['summary']['total_amount'])->toEqual(160.0);
});

it('scopes a non-admin employee to the lines they sold', function (): void {
    $employee = User::factory()->create([
        'tenant_id' => $this->world->tenant->id,
        'default_branch_id' => $this->world->branch->id,
        'type' => 'employee',
        'is_admin' => 0,
    ]);
    foreach (['report.sale item', 'report.sales overview'] as $name) {
        $employee->givePermissionTo(Permission::where('tenant_id', $this->world->tenant->id)->where('name', $name)->first());
    }

    ($this->sell)($employee, [[
        'productId' => $this->world->product->id,
        'quantity' => 1,
        'unitPrice' => (float) $this->world->product->mrp,
        'discount' => 0,
    ]]);

    Sanctum::actingAs($employee);
    $data = ($this->report)([]);

    expect($data['rows'])->toHaveCount(1)
        ->and($data['rows'][0]['category_name'])->toBe('General')
        ->and($data['summary']['total_amount'])->toEqual(50.0);
});
