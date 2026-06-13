{{--
    Pill chip. Tone: soft | success | danger | info | warning.
    Usage:  <x-rent-out.view.chip tone="info">0002</x-rent-out.view.chip>
--}}
@props(['tone' => 'soft', 'icon' => null])

<span {{ $attributes->merge(['class' => 'chip chip-' . $tone]) }}>
    @if ($icon)<i class="fa {{ $icon }}"></i>@endif{{ $slot }}
</span>
