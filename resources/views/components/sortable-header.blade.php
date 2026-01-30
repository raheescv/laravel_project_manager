<a href="#" class="text-decoration-none text-dark align-items-center gap-1" wire:click.prevent="sortBy('{{ $field }}')">
    {{ ucWords($label) }}
    @if ($sortField === $field)
        <small>{!! sortDirection($direction ?? $sortDirection) !!}</small>
    @else
        <small class="text-muted opacity-50"><i class="fa fa-sort fs-6"></i></small>
    @endif
</a>

