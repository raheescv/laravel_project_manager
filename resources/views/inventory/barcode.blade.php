<!DOCTYPE html>
<html lang="en" dir="ltr">

@php
    function convertToUnits($value)
    {
        if (is_numeric($value)) {
            if ($value == 100) {
                return $value . '%';
            }
            return $value . 'px';
        }
        return $value;
    }

    function alignToFlex($align)
    {
        return match ($align ?? 'left') {
            'right' => 'flex-end',
            'center' => 'center',
            default => 'flex-start',
        };
    }

    function getElementStyle($element, $settings)
    {
        $style = [];
        foreach (['top', 'left', 'width', 'height'] as $prop) {
            $style[$prop] = convertToUnits($settings['elements'][$element][$prop] ?? 0);
        }

        $align = $settings[$element]['align'] ?? null;
        if ($align) {
            $style['display'] = 'flex';
            $style['justify-content'] = alignToFlex($align);
        }

        $value = implode('; ', array_map(fn($key, $value) => "$key: $value", array_keys($style), $style));

        return $value;
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

        html {
            overflow: hidden;
        }

        body {
            margin: 0;
            padding: 0;
            overflow: hidden;

            @if (!empty($isPreview))
                min-height: 100vh;
                display: flex;
                align-items: flex-start;
                justify-content: center;
                padding-top: 28px;
                background: #ffffff;
            @endif
        }

        .barcode-container {
            position: relative;
            width: {{ $settings['width'] ?? 40 }}mm;
            height: {{ $settings['height'] ?? 30 }}mm;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            page-break-inside: avoid;
            background: #fff;
            border: 2px solid transparent;
            border-radius: 0;

            @if (!empty($isPreview))
                border-color: #bfdbfe;
                box-shadow: inset 0 0 0 1px rgba(191, 219, 254, 0.4);
            @endif
        }

        .barcode-element {
            position: absolute;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }

        .barcode-element>bdo,
        .barcode-element>span {
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .product-name {
            font-size: {{ $settings['product_name']['font_size'] }}px;
            line-height: 1.1;
            font-weight: 600;
        }

        .product-name-arabic {
            font-size: {{ $settings['product_name_arabic']['font_size'] }}px;
            line-height: 1.1;
            direction: rtl;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            font-weight: 500;
        }

        .company-name {
            font-size: {{ $settings['company_name']['font_size'] }}px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .product-size {
            font-size: {{ $settings['size']['font_size'] }}px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .barcode-image img {
            max-width: 100%;
            height: auto;
        }

        .price {
            font-size: {{ $settings['price']['font_size'] }}px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .price:before {
            content: "QR ";
            font-size: 90%;
        }

        .price-arabic {
            font-size: {{ $settings['price_arabic']['font_size'] ?? 14 }}px;
            font-weight: bold;
            direction: rtl;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            unicode-bidi: plaintext;
        }

        .price-arabic:before {
            content: 'ق ر';
            font-size: 90%;
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

<body>
    <div class="barcode-container">
        @if (($settings['size']['visible'] ?? true) && !empty($product->size))
            <div id="product-size" class="barcode-element product-size" style="{{ getElementStyle('size', $settings) }}">
                <span>Size: {{ $product->size }}</span>
            </div>
        @endif

        @if ($settings['product_name']['visible'] ?? true)
            <div id="product-name" class="barcode-element product-name"
                style="{{ getElementStyle('product_name', $settings) }}">
                <bdo
                    dir="ltr">{{ mb_substr($product->name, 0, (int) $settings['product_name']['char_limit']) }}</bdo>
            </div>
        @endif

        @if ($settings['product_name_arabic']['visible'] ?? true)
            <div id="product-name-arabic" class="barcode-element product-name-arabic"
                style="{{ getElementStyle('product_name_arabic', $settings) }}">
                <bdo
                    dir="rtl">{{ mb_substr($product->name_arabic, 0, (int) $settings['product_name_arabic']['char_limit']) }}</bdo>
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
                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($barcode, $barcodeType, $scale, $height, [0, 0, 0], $showCode) }}"
                    alt="{{ $barcode }}">
            </div>
        @endif

        @if ($settings['company_name']['visible'] ?? true)
            <div class="barcode-element company-name" style="{{ getElementStyle('company_name', $settings) }}">
                <span>{{ $company_name }}</span>
            </div>
        @endif

        @if (($settings['logo']['visible'] ?? false) && !empty($company_logo))
            <div class="barcode-element company-logo" style="{{ getElementStyle('logo', $settings) }}">
                <img src="{{ $company_logo }}" alt="Company Logo">
            </div>
        @endif

        @if ($settings['price']['visible'] ?? true)
            <div class="barcode-element price" style="{{ getElementStyle('price', $settings) }}">
                <span>{{ number_format($product->mrp * $conversionFactor, 2) }}</span>
            </div>
        @endif

        @if ($settings['price_arabic']['visible'] ?? true)
            <div class="barcode-element price-arabic" style="{{ getElementStyle('price_arabic', $settings) }}">
                <span>{{ arabicNumber($product->mrp * $conversionFactor) }}</span>
            </div>
        @endif
    </div>
</body>

</html>
