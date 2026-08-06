@php
    use App\Support\BarcodeFonts;

    $jtWing = (float) ($settings['wing_width'] ?? 25);
    $jtNeck = (float) ($settings['neck_width'] ?? 25);
    $jtHeight = (float) ($settings['height'] ?? 13);
    $jtNeckHeight = min((float) ($settings['neck_height'] ?? 5), $jtHeight);
    $jtPad = (float) ($settings['inner_padding'] ?? 1.5);
    $jtWidth = ($jtWing * 2) + $jtNeck;

    // Usable content box inside a wing.
    $jtContentWidth = max(1, $jtWing - $jtPad * 2);
    $jtContentHeight = max(1, $jtHeight - $jtPad * 2);
    $jtVertical = ($settings['barcode']['orientation'] ?? 'horizontal') === 'vertical';
    // Bars run across the wing when horizontal and along it when vertical, so
    // the bar height is capped by whichever side it actually grows into.
    $jtBarcodeHeight = min((float) ($settings['barcode']['height'] ?? 9), $jtVertical ? $jtContentWidth : $jtContentHeight);
@endphp

{{-- Linked on screen so the preview can cache them, embedded for a print run
     because the PDF renderer cannot fetch anything. --}}
{!! BarcodeFonts::styleTag($settings, empty($isPreview)) !!}

<style>
    @page {
        size: {{ $jtWidth }}mm {{ $jtHeight }}mm;
        margin: 0;
        padding: 0;
    }

    html {
        overflow: hidden;
    }

    body {
        margin: 0;
        padding: 0;
        overflow: hidden;
    }

    .jt-tag {
        position: relative;
        width: {{ $jtWidth }}mm;
        height: {{ $jtHeight }}mm;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        background: #fff;
        overflow: hidden;
        page-break-inside: avoid;
        font-family: {!! BarcodeFonts::templateStack($settings) !!};
        font-weight: {{ BarcodeFonts::templateWeight($settings) }};
        color: #000;
    }

    .jt-wing {
        position: absolute;
        top: 0;
        height: {{ $jtHeight }}mm;
        width: {{ $jtWing }}mm;
        padding: {{ $jtPad }}mm;
        box-sizing: border-box;
        overflow: hidden;
    }

    .jt-wing--left {
        left: 0;
    }

    .jt-wing--right {
        left: {{ $jtWing + $jtNeck }}mm;
    }

    /* Text wing ------------------------------------------------------- */
    .jt-text {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: stretch;
        line-height: 1.15;
    }

    .jt-text--rotated {
        transform: rotate(180deg);
    }

    .jt-line {
        display: block;
        width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .jt-line--bold {
        font-weight: 700;
    }

    /* The face comes from the field's own font setting, printed inline on the
       line itself, so nothing is hardcoded here. */
    .jt-line--rtl {
        direction: rtl;
        unicode-bidi: plaintext;
    }

    /* Barcode wing ---------------------------------------------------- */
    .jt-barcode {
        position: relative;
    }

    .jt-barcode__inner {
        position: absolute;
        top: 50%;
        left: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        @if ($jtVertical)
            width: {{ $jtContentHeight }}mm;
            height: {{ $jtContentWidth }}mm;
            transform: translate(-50%, -50%) rotate(-90deg);
        @else
            width: {{ $jtContentWidth }}mm;
            height: {{ $jtContentHeight }}mm;
            transform: translate(-50%, -50%);
        @endif
    }

    /* The bars take whatever height is left once the number has its line, so
       the number can never be pushed outside the wing and clipped away. */
    .jt-barcode__inner img {
        display: block;
        flex: 1 1 auto;
        min-height: 0;
        width: 100%;
        height: 100%;
        max-height: {{ $jtBarcodeHeight }}mm;
        object-fit: fill;
    }

    .jt-barcode__value {
        flex: 0 0 auto;
        width: 100%;
        text-align: center;
        font-family: {!! BarcodeFonts::blockStack($settings, $settings['barcode'] ?? null) !!};
        letter-spacing: .02em;
        line-height: 1;
        margin-top: .3mm;
        font-size: {{ $settings['barcode']['font_size'] ?? 6 }}px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Preview only - never printed ------------------------------------ */
    .jt-tag--preview {
        outline: 1px solid #bfdbfe;
    }

    .jt-tag--preview .jt-wing {
        outline: 1px dashed #93c5fd;
        outline-offset: -1px;
    }

    .jt-tag--preview .jt-neck {
        position: absolute;
        left: {{ $jtWing }}mm;
        width: {{ $jtNeck }}mm;
        top: {{ ($jtHeight - $jtNeckHeight) / 2 }}mm;
        height: {{ $jtNeckHeight }}mm;
        outline: 1px dashed #93c5fd;
        outline-offset: -1px;
        background: rgba(191, 219, 254, .18);
    }

    .jt-tag--preview .jt-fold {
        position: absolute;
        top: 0;
        bottom: 0;
        left: {{ $jtWidth / 2 }}mm;
        width: 0;
        border-left: 1px dashed #f97316;
    }
</style>
