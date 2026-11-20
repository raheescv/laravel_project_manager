<?php

use App\Actions\Sale\Pos\GetProductsAction;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Models\UniversalUnitConversion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\PosWorld;

/**
 * The till's product grid carries every in-stock variant of a family on its
 * card, so the work per search must not grow with the number of variants:
 * units and Universal UOM rows are loaded once for the whole page.
 *
 * @see GetProductsAction
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);

    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'size' => '40', 'is_selling' => true]);
    DB::table('categories')->where('id', $this->shoe->main_category_id)->update(['sale_visibility_flag' => true]);

    $this->addVariants = function (int $count): void {
        foreach (range(1, $count) as $i) {
            $variant = Product::factory()->variantOf($this->shoe, (string) (40 + $i))->create();
            DB::table('inventories')->insert([
                'tenant_id' => $this->world->tenant->id,
                'branch_id' => $this->world->branch->id,
                'product_id' => $variant->id,
                'quantity' => 2,
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

    $this->queriesFor = function (array $filters): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = (new GetProductsAction())->execute($filters, $this->world->branch->id);
        DB::disableQueryLog();
        expect($response['success'])->toBeTrue();

        return count(DB::getQueryLog());
    };
});

it('runs the same number of queries for a family of 3 or 30 variants', function (): void {
    ($this->addVariants)(2);
    ($this->queriesFor)(['search' => 'Running']);
    $few = ($this->queriesFor)(['search' => 'Running']);

    ($this->addVariants)(28);
    $many = ($this->queriesFor)(['search' => 'Running']);

    expect($many)->toBe($few);
});

it('still gives every variant its own units, including universal conversions', function (): void {
    $box = Unit::create(['name' => 'Box '.Str::random(4), 'code' => 'BX']);
    $pair = Unit::create(['name' => 'Pair '.Str::random(4), 'code' => 'PR']);
    UniversalUnitConversion::create(['base_unit_id' => $this->shoe->unit_id, 'sub_unit_id' => $box->id, 'conversion_factor' => 12]);
    ($this->addVariants)(1);
    $variant = Product::where('parent_id', $this->shoe->id)->first();
    ProductUnit::create(['product_id' => $variant->id, 'sub_unit_id' => $pair->id, 'conversion_factor' => 2]);

    $card = (new GetProductsAction())->execute(['search' => 'Running'], $this->world->branch->id)['data']->first();
    $units = collect($card['variants'])->mapWithKeys(fn (array $v) => [$v['product_id'] => collect($v['units'])->pluck('conversion_factor', 'name')->map(fn ($f) => (float) $f)->all()]);

    expect($units[$this->shoe->id])->toBe([$this->shoe->unit->name => 1.0, $box->name => 12.0])
        ->and($units[$variant->id])->toBe([$this->shoe->unit->name => 1.0, $pair->name => 2.0, $box->name => 12.0])
        ->and($units[$variant->id])->toBe(collect($variant->fresh()->getResolvedUnits())->pluck('conversion_factor', 'name')->map(fn ($f) => (float) $f)->all());
});
