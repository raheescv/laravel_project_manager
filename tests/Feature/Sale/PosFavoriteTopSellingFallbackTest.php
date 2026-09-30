<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PosWorld;

/**
 * With nothing marked favorite, the POS Favorites tab shows the branch's ten
 * best-selling items instead of an empty grid.
 *
 * @see App\Actions\Sale\Pos\GetProductsAction
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
    $this->world->product->update(['is_favorite' => false]);

    $this->addProduct = function (string $name, bool $isFavorite = false): Product {
        $product = $this->world->product->replicate(['barcode']);
        $product->name = $name;
        $product->code = 'P'.Str::upper(Str::random(8));
        $product->is_favorite = $isFavorite;
        $product->save();

        DB::table('inventories')->insert([
            'tenant_id' => $this->world->tenant->id,
            'branch_id' => $this->world->branch->id,
            'product_id' => $product->id,
            'quantity' => 50,
            'batch' => (string) Str::uuid(),
            'cost' => 10,
            'barcode_prefix' => '',
            'barcode_number' => (string) random_int(10000000, 99999999),
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $product;
    };

    $this->sell = function (Product $product, float $quantity, string $status = 'completed'): void {
        $saleId = DB::table('sales')->insertGetId([
            'tenant_id' => $this->world->tenant->id,
            'invoice_no' => 'INV'.Str::upper(Str::random(6)),
            'branch_id' => $this->world->branch->id,
            'account_id' => $this->world->accounts['general_customer'],
            'date' => now()->toDateString(),
            'status' => $status,
            'created_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sale_items')->insert([
            'tenant_id' => $this->world->tenant->id,
            'sale_id' => $saleId,
            'employee_id' => $this->world->user->id,
            'inventory_id' => 0,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'unit_price' => $product->mrp,
            'quantity' => $quantity,
            'discount' => 0,
            'tax' => 0,
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };
});

it('shows the ten best-selling items, most sold first, when no product is a favorite', function (): void {
    foreach (range(1, 12) as $rank) {
        ($this->sell)(($this->addProduct)('Item '.$rank), 100 - $rank);
    }

    $response = $this->getJson($this->world->url('products?category_id=favorite'))->assertOk();

    expect(collect($response->json())->pluck('name')->all())
        ->toBe(collect(range(1, 10))->map(fn ($rank) => 'Item '.$rank)->all())
        ->and($response->headers->get('X-Total-Count'))->toBe('10');
});

it('ranks only completed sales', function (): void {
    $sold = ($this->addProduct)('Sold Ring');
    $drafted = ($this->addProduct)('Drafted Ring');
    ($this->sell)($sold, 1);
    ($this->sell)($drafted, 9, 'draft');

    $response = $this->getJson($this->world->url('products?category_id=favorite'))->assertOk();

    expect(collect($response->json())->pluck('name')->all())->toBe(['Sold Ring']);
});

it('keeps showing the favorites once any product is marked favorite', function (): void {
    ($this->sell)(($this->addProduct)('Best Seller'), 20);
    ($this->addProduct)('Pinned Chain', isFavorite: true);

    $response = $this->getJson($this->world->url('products?category_id=favorite'))->assertOk();

    expect(collect($response->json())->pluck('name')->all())->toBe(['Pinned Chain']);
});

it('does not fall back when a search finds no favorite', function (): void {
    ($this->sell)(($this->addProduct)('Best Seller'), 20);

    $response = $this->getJson($this->world->url('products?category_id=favorite&search=Nothing'))->assertOk();

    expect($response->json())->toBe([]);
});
