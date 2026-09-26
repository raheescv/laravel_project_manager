<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PosWorld;

/**
 * The POS product grid only ever receives 50 rows, so GET /products reports
 * how many items match the filter in X-Total-Count — the grid shows
 * "Showing 50 of N items" from it.
 *
 * @see App\Actions\Sale\Pos\GetProductsAction
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->addProducts = function (int $count, string $prefix): void {
        foreach (range(1, $count) as $index) {
            $product = $this->world->product->replicate(['barcode']);
            $product->name = $prefix.' '.$index;
            $product->code = 'P'.Str::upper(Str::random(8));
            $product->save();

            DB::table('inventories')->insert([
                'tenant_id' => $this->world->tenant->id,
                'branch_id' => $this->world->branch->id,
                'product_id' => $product->id,
                'quantity' => 5,
                'batch' => (string) Str::uuid(),
                'cost' => 10,
                'barcode_prefix' => '',
                'barcode_number' => (string) random_int(10000000, 99999999),
                'created_by' => $this->world->user->id,
                'updated_by' => $this->world->user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    };
});

it('reports the full match count while returning at most 50 rows', function (): void {
    ($this->addProducts)(55, 'Bracelet');

    $response = $this->getJson($this->world->url('products'))->assertOk();

    expect($response->json())->toHaveCount(50)
        ->and($response->headers->get('X-Total-Count'))->toBe('56');
});

it('counts only the items matching the search filter', function (): void {
    ($this->addProducts)(3, 'Headband');
    ($this->addProducts)(2, 'Hair Clip');

    $response = $this->getJson($this->world->url('products?search=Headband'))->assertOk();

    expect($response->json())->toHaveCount(3)
        ->and($response->headers->get('X-Total-Count'))->toBe('3');
});
