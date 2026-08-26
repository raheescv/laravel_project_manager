@props(['source' => null])

@php
    // Icon + colour per channel. A sale created before the `source` column
    // existed has none, and says so rather than pretending to be a web sale.
    $meta = [
        'web' => ['fa-desktop', 'primary'],
        'pos' => ['fa-shopping-cart', 'success'],
        'api' => ['fa-mobile', 'warning'],
        'appointment' => ['fa-calendar', 'info'],
        'import' => ['fa-upload', 'secondary'],
        'migration' => ['fa-database', 'dark'],
    ][$source] ?? ['fa-question-circle', 'secondary'];

    [$icon, $colour] = $meta;
    $label = saleSources()[$source] ?? 'Unknown';
@endphp

<span {{ $attributes->merge(['class' => "badge bg-{$colour} bg-opacity-10 text-{$colour} text-nowrap"]) }} title="Created from: {{ $label }}">
    <i class="fa {{ $icon }} me-1"></i>{{ $label }}
</span>
