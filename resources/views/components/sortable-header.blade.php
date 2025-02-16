<a href="#" wire:click.prevent="sortBy('{{ $field }}')">
    {{ ucWords($label) }}
    @if ($sortField === $field)
        {!! sortDirection($direction) !!}
    @endif
</a>
