{{--
    Glass KPI card that overlaps the hero.
    Usage:
        <x-rent-out.view.kpi tone="info" icon="fa-calendar" label="Days Remaining"
            value="300" sub="days left on tenancy">
            <x-slot:badge><span class="chip chip-info">live</span></x-slot:badge>
        </x-rent-out.view.kpi>
--}}
@props([
    'tone' => 'info',
    'icon',
    'label',
    'value',
    'sub' => null,
    'badge' => null,
    'col' => 'col-6 col-lg-3',
])

<div class="{{ $col }}">
    <div class="kpi k-{{ $tone }}">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="ic"><i class="fa {{ $icon }}"></i></span>
            @if ($badge)
                {{ $badge }}
            @endif
        </div>
        <div class="kpi-label">{{ $label }}</div>
        <div class="kpi-value">{{ $value }}</div>
        @if ($sub)
            <div class="kpi-sub">{{ $sub }}</div>
        @endif
    </div>
</div>
