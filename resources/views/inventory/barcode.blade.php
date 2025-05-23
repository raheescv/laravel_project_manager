<!DOCTYPE html>
<html lang="ar" dir="rtl">

@php
    // Helper functions
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

        .barcode-image {
            text-align: {{ $settings['barcode']['align'] }};
        }

        .barcode-image img {
            width: calc(10mm * {{ $settings['barcode']['scale'] ?? 1 }});
            height: calc({{ $settings['elements']['barcode']['height'] }}mm * {{ $settings['barcode']['scale'] ?? 1 }});
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
        @if ($settings['product_name']['visible'] ?? true)
            <div id="product-name" class="barcode-element product-name" draggable="true" style="{{ getElementStyle('product_name', $settings) }}">
                <b>{{ substr($inventory->product->name, 0, (int) $settings['product_name']['char_limit']) }}</b>
                <div class="element-handle top-left"></div>
                <div class="element-handle top-right"></div>
            </div>
        @endif

        @if ($settings['product_name_arabic']['visible'] ?? true)
            <div id="product-name-arabic" class="barcode-element product-name-arabic" draggable="true" style="{{ getElementStyle('product_name_arabic', $settings) }}">
                <bdo dir="rtl">{{ substr($inventory->product->name_arabic, 0, (int) $settings['product_name_arabic']['char_limit']) }}</bdo>
                <div class="element-handle top-left"></div>
                <div class="element-handle top-right"></div>
            </div>
        @endif

        @if ($settings['barcode']['visible'] ?? true)
            <div class="barcode-element barcode-image" style="{{ getElementStyle('barcode', $settings) }}">
                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($inventory->barcode, 'C128', $settings['elements']['barcode']['width'] ?? 40, $settings['elements']['barcode']['height'] ?? 40, [0, 0, 0], $settings['barcode']['show_value'] ?? false) }}"
                    alt="{{ $inventory->barcode }}">
            </div>
        @endif

        @if ($settings['price']['visible'] ?? true)
            <div class="barcode-element price" style="{{ getElementStyle('price', $settings) }}">
                {{ number_format($inventory->product->mrp, 2) }}
            </div>
        @endif

        @if ($settings['price_arabic']['visible'] ?? true)
            <div class="barcode-element price-arabic" style="{{ getElementStyle('price_arabic', $settings) }}">
                {{ arabicNumber($inventory->product->mrp) }}
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const container = document.querySelector('.barcode-container');
            const elements = document.querySelectorAll('.barcode-element');

            // Initialize draggable elements
            elements.forEach(element => {
                initializeDraggable(element);
                initializeResizable(element);
            });

            function initializeDraggable(element) {
                element.addEventListener('mousedown', startDragging);
                element.addEventListener('touchstart', startDragging, {
                    passive: false
                });
            }

            function startDragging(e) {
                if (e.target.classList.contains('element-handle')) return;
                e.preventDefault();
                const element = e.target.closest('.barcode-element');
                if (!element) return;

                const startX = e.type === 'mousedown' ? e.clientX : e.touches[0].clientX;
                const startY = e.type === 'mousedown' ? e.clientY : e.touches[0].clientY;
                const startLeft = element.offsetLeft;
                const startTop = element.offsetTop;

                function onMove(e) {
                    const currentX = e.type === 'mousemove' ? e.clientX : e.touches[0].clientX;
                    const currentY = e.type === 'mousemove' ? e.clientY : e.touches[0].clientY;

                    const deltaX = currentX - startX;
                    const deltaY = currentY - startY;

                    const newLeft = Math.max(0, Math.min(startLeft + deltaX, container.offsetWidth - element.offsetWidth));
                    const newTop = Math.max(0, Math.min(startTop + deltaY, container.offsetHeight - element.offsetHeight));

                    element.style.left = `${newLeft}px`;
                    element.style.top = `${newTop}px`;

                    // Update Livewire component
                    window.Livewire.dispatch('elementMoved', {
                        elementId: element.id,
                        position: {
                            top: newTop,
                            left: newLeft
                        }
                    });
                }

                function onEnd() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onEnd);
                    document.removeEventListener('touchmove', onMove);
                    document.removeEventListener('touchend', onEnd);
                }

                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onEnd);
                document.addEventListener('touchmove', onMove, {
                    passive: false
                });
                document.addEventListener('touchend', onEnd);
            }

            function initializeResizable(element) {
                const handles = element.querySelectorAll('.element-handle');
                handles.forEach(handle => {
                    handle.addEventListener('mousedown', startResizing);
                    handle.addEventListener('touchstart', startResizing, {
                        passive: false
                    });
                });
            }

            function startResizing(e) {
                e.preventDefault();
                e.stopPropagation();

                const handle = e.target;
                const element = handle.closest('.barcode-element');
                const startX = e.type === 'mousedown' ? e.clientX : e.touches[0].clientX;
                const startY = e.type === 'mousedown' ? e.clientY : e.touches[0].clientY;
                const startWidth = element.offsetWidth;
                const startHeight = element.offsetHeight;
                const isRight = handle.classList.contains('top-right');

                function onMove(e) {
                    const currentX = e.type === 'mousemove' ? e.clientX : e.touches[0].clientX;
                    const currentY = e.type === 'mousemove' ? e.clientY : e.touches[0].clientY;

                    const deltaX = currentX - startX;
                    const newWidth = Math.max(50, isRight ? startWidth + deltaX : startWidth - deltaX);
                    const newHeight = Math.max(20, startHeight + (currentY - startY));

                    element.style.width = `${newWidth}px`;
                    element.style.height = `${newHeight}px`;

                    if (!isRight) {
                        element.style.left = `${element.offsetLeft - (newWidth - startWidth)}px`;
                    }

                    // Update Livewire component
                    window.Livewire.dispatch('elementResized', {
                        elementId: element.id,
                        size: {
                            width: newWidth,
                            height: newHeight
                        }
                    });
                }

                function onEnd() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onEnd);
                    document.removeEventListener('touchmove', onMove);
                    document.removeEventListener('touchend', onEnd);
                }

                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onEnd);
                document.addEventListener('touchmove', onMove, {
                    passive: false
                });
                document.addEventListener('touchend', onEnd);
            }
        });
    </script>
</body>

</html>
