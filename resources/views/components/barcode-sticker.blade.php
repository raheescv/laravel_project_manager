@props(['inventory', 'settings' => [], 'width' => 40, 'height' => 30])

<div class="barcode-item">
    <div class="product-name">
        {{ substr($inventory->product?->name, 0, (int) ($settings['product_name']['no_of_letters'] ?? 12)) }}
    </div>

    @if ($inventory->product->name_arabic)
        <div class="product-name-ar">
            {{ substr($inventory->product->name_arabic, 0, (int) ($settings['product_arabic_name']['no_of_letters'] ?? 12)) }}
        </div>
    @endif

    <div class="barcode-container">
        @php
            $barcodeType = $settings['barcode']['type'] ?? ($settings['barcode_image']['type'] ?? 'C128');
        @endphp
        <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($inventory->barcode, $barcodeType, $settings['barcode_image']['width'] ?? 3, $settings['barcode_image']['height'] ?? 40) }}"
            alt="Sample Barcode">
    </div>

    <div class="barcode-number">
        {{ $inventory->barcode }}
    </div>

    <div class="price-container">
        <div class="price">
            QR {{ number_format($inventory->product->mrp, 2) }}
        </div>
        <div class="price-ar">
            {{ $inventory->product->arabic_mrp }} : ق ر
        </div>
    </div>
</div>
