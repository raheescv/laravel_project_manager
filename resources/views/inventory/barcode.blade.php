<!DOCTYPE html>
<html lang="ar" dir="rtl">

@php
    function convertToUnits($value)
    {
        if (is_numeric($value)) {
            return $value . 'px';
        }
        return $value;
    }

    function getElementStyle($element, $settings)
    {
        $style = [];
        foreach (['top', 'left', 'width', 'height'] as $prop) {
            $style[$prop] = convertToUnits($settings['elements'][$element][$prop] ?? 0);
        }
        return implode('; ', array_map(fn($key, $value) => "$key: $value", array_keys($style), $style));
    }
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Barcode Print</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }

        @media print {
            .page-break {
                page-break-after: always;
            }
        }

        /* Using system fonts instead of DejaVu Sans */

        .barcode-container {
            position: relative;
            width: {{ $settings['width'] ?? 40 }}mm;
            height: {{ $settings['height'] ?? 30 }}mm;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            page-break-inside: avoid;
        }

        .barcode-element {
            position: absolute;
            overflow: hidden;
            margin: 0;
            padding: 0;
            opacity: v-bind("element.visible ? 1 : 0");
            transition: all 0.3s ease;
        }

        .product-name {
            font-size: {{ $settings['product_name']['font_size'] }}px;
            text-align: {{ $settings['product_name']['align'] }};
            line-height: 1.1;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }

        .product-name-arabic {
            font-size: {{ $settings['product_name_arabic']['font_size'] }}px;
            text-align: right;
            line-height: 1.1;
            direction: rtl;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 2px;
            unicode-bidi: plaintext;
            writing-mode: horizontal-tb;
            text-orientation: mixed;
        }

        .company-name {
            font-size: {{ $settings['company_name']['font_size'] }}px;
            text-align: {{ $settings['company_name']['align'] }};
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .product-size {
            font-size: {{ $settings['size']['font_size'] }}px;
            text-align: {{ $settings['size']['align'] }};
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .barcode-image img {
            width: {{ $settings['elements']['barcode']['width'] ?? 180 }};
            height: {{ $settings['elements']['barcode']['height'] ?? 40 }};
        }

        .barcode-value {
            text-align: center;
            font-size: {{ $settings['barcode']['font_size'] ?? 12 }}px;
            margin-top: 2px;
            font-family: monospace;
            line-height: 1;
        }

        .price {
            font-size: {{ $settings['price']['font_size'] }}px;
            text-align: {{ $settings['price']['align'] }};
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .price:before {
            content: "QR ";
            font-size: 90%;
        }

        .price-arabic {
            font-size: {{ $settings['price_arabic']['font_size'] ?? 14 }}px;
            text-align: {{ $settings['price_arabic']['align'] ?? 'right' }};
            font-weight: bold;
            direction: rtl;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            unicode-bidi: plaintext;
        }

        .price-arabic:before {
            content: 'ق ر';
            font-size: 90%;
        }

        [draggable="true"] {
            cursor: move;
        }

        .barcode-element.dragging {
            opacity: 0.5;
            z-index: 1000;
        }

        .element-handle {
            position: absolute;
            width: 10px;
            height: 10px;
            background: #007bff;
            border-radius: 50%;
            cursor: pointer;
            display: none;
        }

        .barcode-element:hover .element-handle {
            display: block;
        }
    </style>
</head>

<body style="margin:0;padding:0;">
    <div class="barcode-container">
        @if (($settings['size']['visible'] ?? true) && !empty($product->size))
            <div id="product-size" class="barcode-element"
                style="{{ getElementStyle('size', $settings) }}; font-size: {{ $settings['size']['font_size'] ?? 10 }}px; text-align: {{ $settings['size']['align'] ?? 'left' }};">
                Size: {{ $product->size }}
            </div>
        @endif
        @if ($settings['product_name']['visible'] ?? true)
            <div id="product-name" class="barcode-element product-name" draggable="true" style="{{ getElementStyle('product_name', $settings) }}">
                <b>{{ substr($product->name, 0, (int) $settings['product_name']['char_limit']) }}</b>
                <div class="element-handle top-left"></div>
                <div class="element-handle top-right"></div>
            </div>
        @endif

        @if ($settings['product_name_arabic']['visible'] ?? true)
            <div id="product-name-arabic" class="barcode-element product-name-arabic" draggable="true" style="{{ getElementStyle('product_name_arabic', $settings) }}">
                <bdo dir="rtl">{{ substr($product->name_arabic, 0, (int) $settings['product_name_arabic']['char_limit']) }}</bdo>
                <div class="element-handle top-left"></div>
                <div class="element-handle top-right"></div>
            </div>
        @endif

        @if ($settings['barcode']['visible'] ?? true)
            <div class="barcode-element barcode-image" style="{{ getElementStyle('barcode', $settings) }}">
                @php
                    $barcodeType = $settings['barcode']['type'] ?? 'C128';
                    $scale = $settings['barcode']['scale'];
                    $height = $settings['elements']['barcode']['height'] ?? 40;
                    $showCode = $settings['barcode']['show_value'] ?? true;
                @endphp
                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($barcode, $barcodeType, $scale, $height, [0, 0, 0], $showCode) }}" alt="{{ $barcode }}">
            </div>
        @endif

        @if ($settings['size']['visible'] ?? true && !empty($product->size))
            <div class="barcode-element product-size" style="{{ getElementStyle('size', $settings) }}">
                Size : {{ $product->size }}
            </div>
        @endif

        @if ($settings['company_name']['visible'] ?? true)
            <div class="barcode-element company-name" style="{{ getElementStyle('company_name', $settings) }}">
                {{ $company_name }}
            </div>
        @endif

        @if ($settings['price']['visible'] ?? true)
            <div class="barcode-element price" style="{{ getElementStyle('price', $settings) }}">
                {{ number_format($product->mrp * $conversionFactor, 2) }}
            </div>
        @endif

        @if ($settings['price_arabic']['visible'] ?? true)
            <div class="barcode-element price-arabic" style="{{ getElementStyle('price_arabic', $settings) }}">
                {{ arabicNumber($product->mrp * $conversionFactor) }}
            </div>
        @endif
    </div>
</body>

</html>
