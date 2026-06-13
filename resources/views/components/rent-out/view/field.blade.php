{{--
    One dt/dd definition row inside a <div class="dl"> … </div> grid.
    Pass a plain :value, or use the slot for rich content (chips, buttons).
    Usage:
        <x-rent-out.view.field icon="fa-hashtag" label="Reference No" :value="$ref" />
        <x-rent-out.view.field icon="fa-circle-o" label="Status">
            <span class="chip chip-occupied"><span class="dot"></span> Occupied</span>
        </x-rent-out.view.field>
--}}
@props([
    'icon' => null,
    'label',
    'value' => null,
])

<div class="dt row-line">@if ($icon)<i class="fa {{ $icon }}"></i>@endif {{ $label }}</div>
<div class="dd row-line">
    @if (trim($slot) !== '')
        {{ $slot }}
    @else
        {{ ($value ?? '') !== '' ? $value : '—' }}
    @endif
</div>
