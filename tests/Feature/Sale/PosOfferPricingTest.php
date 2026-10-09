<?php

use App\Helpers\SaleHelper;
use App\Models\Configuration;
use App\Models\ProductPrice;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosWorld;

/**
 * An offer price on the POS is shown as the real price (MRP) less a per-unit
 * offer discount, so the receipt keeps the original price and the discount
 * grows with the quantity.
 *
 * @see App\Actions\Sale\Pos\AddItemAction
 */
beforeEach(function (): void {
    $this->world = PosWorld::create(price: 280);
    $this->actingAs($this->world->user);
    session(['branch_id' => $this->world->branch->id]);
    Configuration::updateOrCreate(['key' => 'default_quantity'], ['value' => '1']);

    ProductPrice::create([
        'product_id' => $this->world->product->id,
        'price_type' => 'offer',
        'amount' => 270,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'status' => 'active',
    ]);

    $this->inventoryId = DB::table('inventories')->where('product_id', $this->world->product->id)->value('id');
});

it('adds an offer item at MRP with the offer as its discount', function (): void {
    $item = $this->postJson($this->world->url('pos/add-item'), [
        'inventory_id' => $this->inventoryId,
        'employee_id' => $this->world->user->id,
        'sale_type' => 'offer',
    ])->assertOk()->json();

    expect((float) $item['unit_price'])->toBe(280.0)
        ->and((float) $item['discount'])->toBe(10.0)
        ->and((float) $item['offer_unit_discount'])->toBe(10.0)
        ->and((float) $item['offer_price'])->toBe(270.0)
        ->and($item['offer_label'])->toBe('Offer')
        ->and((float) $item['total'])->toBe(270.0);
});

it('keeps normal-sale items at MRP with no discount', function (): void {
    $item = $this->postJson($this->world->url('pos/add-item'), [
        'inventory_id' => $this->inventoryId,
        'employee_id' => $this->world->user->id,
        'sale_type' => 'normal',
    ])->assertOk()->json();

    expect((float) $item['unit_price'])->toBe(280.0)
        ->and((float) $item['discount'])->toBe(0.0)
        ->and((float) $item['offer_unit_discount'])->toBe(0.0)
        ->and($item['offer_label'])->toBeNull();
});

it('scales the offer discount with the quantity on update', function (): void {
    $item = $this->postJson($this->world->url('pos/add-item'), [
        'inventory_id' => $this->inventoryId,
        'employee_id' => $this->world->user->id,
        'sale_type' => 'offer',
    ])->json();

    $updated = $this->postJson($this->world->url('pos/update-item'), [
        'key' => 'k',
        'item' => ['quantity' => 3] + $item,
    ])->assertOk()->json();

    expect((float) $updated['discount'])->toBe(30.0)
        ->and((float) $updated['gross_amount'])->toBe(840.0)
        ->and((float) $updated['total'])->toBe(810.0);

    $manual = $this->postJson($this->world->url('pos/update-item'), [
        'key' => 'k',
        'item' => ['quantity' => 3, 'discount' => 5, 'offer_unit_discount' => 0] + $item,
    ])->json();

    expect((float) $manual['discount'])->toBe(5.0)->and((float) $manual['total'])->toBe(835.0);
});

it('sends the original price beside the offer price for the product grid', function (): void {
    $product = collect($this->getJson($this->world->url('products?sale_type=offer'))->assertOk()->json())
        ->firstWhere('product_id', $this->world->product->id);

    expect((float) $product['mrp'])->toBe(270.0)
        ->and((float) $product['original_price'])->toBe(280.0);
});

it('prints the pre-discount value as Net Value so the receipt adds up to the total', function (): void {
    Configuration::updateOrCreate(['key' => 'enable_discount_in_print'], ['value' => 'yes']);
    $this->actingAs($this->world->user, 'sanctum');

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload([
        'items' => [['productId' => $this->world->product->id, 'quantity' => 1, 'unitPrice' => 280, 'discount' => 10]],
        'totalPayment' => 270,
    ]))->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->latest('id')->firstOrFail();
    $html = (new SaleHelper())->saleInvoice($sale->id);
    $row = fn (string $label): string => preg_match('/'.$label.'.*?<\/tr>/s', $html, $match) ? strip_tags($match[0]) : '';

    expect((float) $sale->gross_amount)->toBe(280.0)
        ->and($row('Net Value'))->toContain('280.00')
        ->and($row('Discount \('))->toContain('10.00')
        ->and($row('Total \('))->toContain('270.00')
        ->and($html)->toMatch('/You saved \S* ?10\.00 on this purchase/');
});

it('leaves the savings line off a receipt with no discount', function (): void {
    Configuration::updateOrCreate(['key' => 'enable_discount_in_print'], ['value' => 'yes']);
    $this->actingAs($this->world->user, 'sanctum');

    $this->postJson($this->world->url('/api/v1/sale'), $this->world->salePayload())->assertSuccessful();

    $sale = Sale::withoutGlobalScopes()->latest('id')->firstOrFail();

    expect((new SaleHelper())->saleInvoice($sale->id))->not->toContain('You saved');
});
