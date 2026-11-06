<?php

use App\Models\Sale;
use App\Models\SaleItem;

/**
 * The Sale Item report's Discount column shows the line discount plus the line's
 * share of the invoice-level other discount, without touching any stored amount.
 *
 * @see App\Models\SaleItem::getDisplayDiscountAttribute()
 */
function saleItemWith(float $discount, float $total, float $saleOtherDiscount, float $saleTotal): SaleItem
{
    $item = (new SaleItem())->forceFill(['discount' => $discount, 'total' => $total]);
    $item->setRelation('sale', (new Sale())->forceFill(['other_discount' => $saleOtherDiscount, 'total' => $saleTotal]));

    return $item;
}

it('adds the other-discount share to the line discount', function (): void {
    $item = saleItemWith(discount: 10, total: 1000, saleOtherDiscount: 50, saleTotal: 2000);

    expect($item->effective_total)->toBe(975.0)
        ->and($item->other_discount_share)->toBe(25.0)
        ->and($item->display_discount)->toBe(35.0)
        ->and($item->discount)->toEqual(10)
        ->and($item->total)->toEqual(1000);
});

it('shows only the line discount when the sale has no other discount', function (): void {
    $item = saleItemWith(discount: 5, total: 300, saleOtherDiscount: 0, saleTotal: 300);

    expect($item->other_discount_share)->toBe(0.0)
        ->and($item->display_discount)->toBe(5.0);
});
