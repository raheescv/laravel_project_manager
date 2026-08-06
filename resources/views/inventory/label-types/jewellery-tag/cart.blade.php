<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Barcode Print</title>
    @include('inventory.label-types.jewellery-tag.styles')
    <style>
        /* One tag per page: a roll feeds label to label. page-break-before on
           every tag but the first avoids a trailing blank page. */
        .jt-tag+.jt-tag {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @foreach ($cartItems as $item)
        @php
            $itemType = $item['item_type'] ?? 'inventory';
            $product = null;
            $inventory = null;
            $barcode = '';
            $conversionFactor = 1;

            if ($itemType === 'product_unit') {
                $productUnit = \App\Models\ProductUnit::with('product.unit', 'subUnit')->find(
                    $item['product_unit_id'] ?? null,
                );
                if ($productUnit) {
                    $product = $productUnit->product;
                    $barcode = $productUnit->barcode;
                    $conversionFactor = $productUnit->conversion_factor;
                }
            } else {
                $inventory = \App\Models\Inventory::with('product.unit')->find($item['inventory_id'] ?? null);
                if ($inventory) {
                    $product = $inventory->product;
                    $barcode = $inventory->barcode;
                    $conversionFactor = 1;
                }
            }
        @endphp
        @if ($product)
            @for ($i = 1; $i <= $item['quantity']; $i++)
                @include('inventory.label-types.jewellery-tag.label')
            @endfor
        @endif
    @endforeach
</body>

</html>
