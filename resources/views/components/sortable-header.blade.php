<a href="#" wire:click.prevent="sortBy('{{ $field }}')">
    {{ $label }}
    @if ($sortField === $field)
        {!! sortDirection($direction) !!}
    @endif
</a>
