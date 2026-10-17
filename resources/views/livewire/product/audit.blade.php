<div>
    <x-audit.sheet :audits="$audits" emptyMessage="No audit entries found for this product." />
    @if ($audits->count() >= \App\Livewire\Product\Audit::LIMIT)
        <small class="text-body-secondary d-block mt-2">Showing the latest {{ number_format(\App\Livewire\Product\Audit::LIMIT) }} entries.</small>
    @endif
</div>
