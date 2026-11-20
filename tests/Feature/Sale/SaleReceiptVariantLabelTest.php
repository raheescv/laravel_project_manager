<?php

use App\Actions\Sale\CreateAction;
use App\Helpers\SaleHelper;
use App\Models\Configuration;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Tests\Support\PosWorld;

/**
 * The sale receipt names a product with its size and colour
 * ("Running Shoe - 42 / Blue") so two pairs of one model can be told apart,
 * without repeating a size or colour the name already spells out.
 */
beforeEach(function (): void {
    $this->world = PosWorld::create();
    Configuration::updateOrCreate(['key' => 'print_item_label'], ['value' => 'product']);

    $this->shoe = $this->world->product;
    $this->shoe->update(['name' => 'Running Shoe', 'size' => '41', 'color' => 'Black']);

    $this->sell = function (Product $product): int {
        $inventoryId = DB::table('inventories')->where('product_id', $product->id)->where('branch_id', $this->world->branch->id)->value('id');

        return (new CreateAction())->execute([
            'branch_id' => $this->world->branch->id,
            'account_id' => $this->world->accounts['general_customer'],
            'date' => today()->toDateString(),
            'sale_type' => 'normal',
            'status' => 'completed',
            'gross_amount' => 50, 'item_discount' => 0, 'tax_amount' => 0, 'other_discount' => 0, 'freight' => 0, 'round_off' => 0, 'paid' => 50,
            'items' => [[
                'inventory_id' => $inventoryId, 'product_id' => $product->id, 'unit_id' => $product->unit_id, 'employee_id' => $this->world->user->id,
                'unit_price' => 50, 'quantity' => 1, 'conversion_factor' => 1, 'discount' => 0, 'tax' => 0,
            ]],
            'payments' => [['payment_method_id' => $this->world->cashAccountId, 'amount' => 50, 'date' => today()->toDateString()]],
            'comboOffers' => [],
        ], $this->world->user->id)['data']->id;
    };
});

it('prints a family product with its size and colour', function (): void {
    Product::factory()->variantOf($this->shoe, '42', 'Blue')->create();

    $html = (new SaleHelper())->saleInvoice(($this->sell)($this->shoe));

    expect($html)->toContain('Running Shoe - 41 / Black');
});

it('prints a standalone product with its size and colour too', function (): void {
    $html = (new SaleHelper())->saleInvoice(($this->sell)($this->shoe));

    expect($html)->toContain('Running Shoe - 41 / Black');
});

it('does not repeat a size or colour the name already has, and prints a bare product plainly', function (): void {
    $this->shoe->update(['name' => 'Polo Shirt Black XL', 'size' => 'XL', 'color' => 'Black']);
    expect((new SaleHelper())->saleInvoice(($this->sell)($this->shoe)))->toContain('Polo Shirt Black XL')->not->toContain('Polo Shirt Black XL -');

    $this->shoe->update(['name' => 'Gift Card', 'size' => null, 'color' => null]);
    expect($this->shoe->fresh()->displayName())->toBe('Gift Card');
});
