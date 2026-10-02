<?php

use App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus;
use App\Models\LocalPurchaseOrder;
use App\Models\LocalPurchaseOrderItem;
use App\Models\Product;

function renderLpoPrint(?string $description): Illuminate\Testing\TestView
{
    $product = new Product(['name' => 'Steel Pipe', 'description' => $description]);

    $item = new LocalPurchaseOrderItem(['quantity' => 2, 'rate' => 10]);
    $item->setRelation('product', $product);

    $order = new LocalPurchaseOrder(['status' => LocalPurchaseOrderStatus::PENDING]);
    $order->id = 1;
    $order->setRelation('items', collect([$item]));

    return test()->view('local-purchase-order.print', [
        'order' => $order,
        'companyLogo' => null,
        'companyName' => 'Acme',
        'companyAddress' => '',
        'companyPhone' => '',
    ]);
}

it('prints the product description under the item name', function (): void {
    renderLpoPrint('2 inch galvanised, 6m length')
        ->assertSeeInOrder(['Steel Pipe', '2 inch galvanised, 6m length'])
        ->assertSee('class="item-desc"', false);
});

it('omits the description line when the product has none', function (): void {
    renderLpoPrint(null)
        ->assertSee('Steel Pipe')
        ->assertDontSee('class="item-desc"', false);
});
