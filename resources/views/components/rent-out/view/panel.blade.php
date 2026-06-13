{{--
    Premium info panel with an icon header.
    Usage:
        <x-rent-out.view.panel icon="fa-building" title="Property & Customer" sub="Unit and tenant details">
            <x-slot:tools>…optional right-aligned header content…</x-slot:tools>
            …body…
        </x-rent-out.view.panel>
--}}
@props([
    'icon',
    'title',
    'sub' => null,
    'tools' => null,
    'flush' => false,
])

<div {{ $attributes->merge(['class' => 'panel h-100']) }}>
    <div class="panel-head">
        <span class="ph-ic"><i class="fa {{ $icon }}"></i></span>
        <div class="min-w-0">
            <p class="panel-title">{{ $title }}</p>
            @if ($sub)
                <p class="panel-sub">{{ $sub }}</p>
            @endif
        </div>
        @if ($tools)
            <div class="ms-auto">{{ $tools }}</div>
        @endif
    </div>
    <div class="{{ $flush ? '' : 'panel-pad' }}">
        {{ $slot }}
    </div>
</div>
