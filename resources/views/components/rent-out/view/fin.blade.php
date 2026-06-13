{{--
    Financial mini-cell (Total / Discount / Paid, or compact key facts).
    Usage:
        <x-rent-out.view.fin label="Total" value="12,000.00" />
        <x-rent-out.view.fin label="Paid" value="0.00" tone="paid" />
--}}
@props([
    'label',
    'value' => null,
    'tone' => null,        {{-- paid | total --}}
    'fill' => false,       {{-- flex-fill for the compact 3-up row --}}
    'valueSize' => null,   {{-- e.g. 14px for compact cells --}}
])

<div @class(['fin', 'is-paid' => $tone === 'paid', 'is-total' => $tone === 'total', 'flex-fill' => $fill])>
    <div class="lab">{{ $label }}</div>
    <div class="val" @if ($valueSize) style="font-size:{{ $valueSize }};" @endif>
        @if (trim($slot) !== ''){{ $slot }}@else{{ $value }}@endif
    </div>
</div>
