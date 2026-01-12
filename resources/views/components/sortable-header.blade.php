<a href="#" class="text-decoration-none text-dark d-flex align-items-center gap-1" wire:click.prevent="sortBy('{{ $field }}')">
    {{ ucWords($label) }}
    @if ($sortField === $field)
        <small>{!! sortDirection($direction ?? $sortDirection) !!}</small>
    @else
        <small class="text-muted opacity-50"><i class="demo-psi-arrow-up-down fs-6"></i></small>
    @endif
</a>

