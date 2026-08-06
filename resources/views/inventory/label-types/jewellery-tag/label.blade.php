{{--
    One jewellery tag. Shared by preview, single print and cart print so the
    three can never drift apart.

    Expects: $settings, $product, $barcode, $conversionFactor
    Optional: $inventory, $isPreview
--}}
@php
    use App\Support\BarcodeFonts;
    use App\Support\BarcodeLabel;

    $jtBarcodeOnLeft = ($settings['barcode_wing'] ?? 'left') === 'left';
    $jtRotateText = (bool) ($settings['rotate_text_wing'] ?? true);
    $jtShowValue = (bool) ($settings['barcode']['show_value'] ?? true);
    $jtBarcodeVisible = (bool) ($settings['barcode']['visible'] ?? true);
    $jtInventory = $inventory ?? null;
    $jtFields = BarcodeLabel::orderedFields($settings);
    $jtPreview = ! empty($isPreview);
@endphp

<div class="jt-tag {{ $jtPreview ? 'jt-tag--preview' : '' }}">
    @if ($jtPreview)
        <div class="jt-neck"></div>
        <div class="jt-fold"></div>
    @endif

    {{-- Barcode wing: the barcode and nothing else. --}}
    <div class="jt-wing jt-barcode {{ $jtBarcodeOnLeft ? 'jt-wing--left' : 'jt-wing--right' }}">
        @if ($jtBarcodeVisible && ! empty($barcode))
            <div class="jt-barcode__inner">
                <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($barcode, $settings['barcode']['type'] ?? 'C128', $settings['barcode']['scale'] ?? 2, 40, [0, 0, 0], false) }}"
                    alt="{{ $barcode }}">
                @if ($jtShowValue)
                    <div class="jt-barcode__value">{{ $barcode }}</div>
                @endif
            </div>
        @endif
    </div>

    {{-- Text wing: name, qty, price, size. Rotated so it reads upright once folded. --}}
    <div
        class="jt-wing jt-text {{ $jtBarcodeOnLeft ? 'jt-wing--right' : 'jt-wing--left' }} {{ $jtRotateText ? 'jt-text--rotated' : '' }}">
        @foreach ($jtFields as $jtKey => $jtField)
            @php
                $jtValue = BarcodeLabel::fieldValue($jtKey, $jtField, $product, (float) $conversionFactor, $jtInventory);
            @endphp
            @if ($jtValue !== '')
                <span
                    class="jt-line {{ ($jtField['bold'] ?? false) ? 'jt-line--bold' : '' }} {{ $jtKey === 'product_name_arabic' ? 'jt-line--rtl' : '' }}"
                    style="font-size: {{ $jtField['font_size'] ?? 6 }}px; text-align: {{ $jtField['align'] ?? 'left' }}; font-family: {!! BarcodeFonts::blockStack($settings, $jtField) !!};">{{ $jtValue }}</span>
            @endif
        @endforeach
    </div>
</div>
