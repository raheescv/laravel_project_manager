<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PosWorld;

/**
 * The POS grid shows a size / colour family as ONE card carrying its variants
 * (the till then asks for size and colour); a product with a single item, or a
 * search that narrows a family down to one item, stays a plain card.
 *
 * @see App\Actions\Sale\Pos\GetProductsAction
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'size' => '41', 'color' => '#ff0000', 'is_selling' => true]);
    DB::table('categories')->where('id', $this->shoe->main_category_id)->update(['sale_visibility_flag' => true]);

    $this->stock = function (Product $product, float $quantity): void {
        DB::table('inventories')->insert([
            'tenant_id' => $this->world->tenant->id,
            'branch_id' => $this->world->branch->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'batch' => (string) Str::uuid(),
            'cost' => 10,
            'barcode_prefix' => '',
            'barcode_number' => (string) random_int(10000000, 99999999),
            'created_by' => $this->world->user->id,
            'updated_by' => $this->world->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    };

    $this->blue = Product::factory()->variantOf($this->shoe, '42', 'Blue')->create(['barcode_number' => '77004242', 'mrp' => 80]);
    $this->plain = Product::factory()->variantOf($this->shoe, '43')->create(['mrp' => 60]);
    ($this->stock)($this->blue, 3);
    ($this->stock)($this->plain, 1);
});

it('shows a family as one card that carries its variants, sizes and colours', function (): void {
    $cards = collect($this->getJson($this->world->url('products'))->assertOk()->json());

    $family = $cards->firstWhere('name', 'Running Shoe');
    expect($cards->where('name', 'Running Shoe'))->toHaveCount(1)
        ->and($family['variants'])->toHaveCount(3)
        ->and($family['sizes'])->toBe(['41', '42', '43'])
        ->and(collect($family['colours'])->pluck('name')->all())->toBe(['Red', 'Blue'])
        ->and((float) $family['stock'])->toBe(104.0)
        ->and((float) $family['mrp'])->toBe(50.0)
        ->and((float) $family['mrp_max'])->toBe(80.0);

    $blue = collect($family['variants'])->firstWhere('size', '42');
    expect($blue['color_name'])->toBe('Blue')
        ->and($blue['product_id'])->toBe($this->blue->id)
        ->and($blue['id'])->not->toBe($family['variants'][0]['id']);
});

it('keeps a family narrowed to one item by a search as a plain card', function (): void {
    $cards = $this->getJson($this->world->url('products?search=77004242'))->assertOk()->json();

    expect($cards)->toHaveCount(1)
        ->and($cards[0])->not->toHaveKey('variants')
        ->and($cards[0]['product_id'])->toBe($this->blue->id)
        ->and($cards[0]['color_name'])->toBe('Blue');
});

it('gives a variant with its own photo that photo and leaves the rest without one', function (): void {
    $this->blue->update(['thumbnail' => '/storage/products/blue-42.jpg']);

    $family = collect($this->getJson($this->world->url('products'))->assertOk()->json())->firstWhere('name', 'Running Shoe');
    $variants = collect($family['variants']);

    expect($variants->firstWhere('product_id', $this->blue->id)['photo'])->toBe('/storage/products/blue-42.jpg')
        ->and($variants->firstWhere('product_id', $this->plain->id)['photo'])->toBeNull();
});

it('fills the grid with fifty cards however many items a family holds', function (): void {
    $sizes = collect(range(44, 99))->map(fn (int $size) => Product::factory()->variantOf($this->shoe, (string) $size)->create());
    $others = Product::factory()->count(55)->sequence(fn ($sequence) => ['name' => 'Zz Item '.$sequence->index])->create(['main_category_id' => $this->shoe->main_category_id, 'unit_id' => $this->shoe->unit_id, 'department_id' => $this->shoe->department_id]);
    $sizes->concat($others)->each(fn (Product $product) => ($this->stock)($product, 2));

    $response = $this->getJson($this->world->url('products'))->assertOk();
    $cards = collect($response->json());

    expect($cards)->toHaveCount(50)
        ->and($cards->firstWhere('name', 'Running Shoe')['variants'])->toHaveCount(59)
        ->and($response->headers->get('X-Total-Count'))->toBe('56');
});

it('counts a family once in the total', function (): void {
    $response = $this->getJson($this->world->url('products'))->assertOk();

    expect($response->headers->get('X-Total-Count'))->toBe((string) count($response->json()));
});

it('brings a starred product\'s variants into Favorites as one family card', function (): void {
    $this->shoe->update(['is_favorite' => true]);
    Product::whereKey([$this->blue->id, $this->plain->id])->update(['is_favorite' => false]);

    $cards = collect($this->getJson($this->world->url('products?category_id=favorite'))->assertOk()->json());

    expect($cards->where('name', 'Running Shoe'))->toHaveCount(1)
        ->and($cards->firstWhere('name', 'Running Shoe')['variants'])->toHaveCount(3);
});

it('brings the whole family into Favorites when only one variant is starred', function (): void {
    $this->shoe->update(['is_favorite' => false]);
    $this->blue->update(['is_favorite' => true]);

    $cards = collect($this->getJson($this->world->url('products?category_id=favorite'))->assertOk()->json());

    expect($cards->firstWhere('name', 'Running Shoe')['variants'])->toHaveCount(3);
});
