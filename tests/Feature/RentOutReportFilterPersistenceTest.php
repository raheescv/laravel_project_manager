<?php

use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\RentOutService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\WithPagination;

/**
 * Bare consumer of the trait so the filter lifecycle can be exercised without
 * dragging a real report component's query and permissions into the test.
 */
class ReportFilterProbe extends Component
{
    use HasRentOutReportFilters, WithPagination;

    public function getDefaultColumns(): array
    {
        return ['date', 'customer', 'amount'];
    }

    protected function buildQuery(): Builder
    {
        return RentOutService::query();
    }

    public function render()
    {
        return '<div></div>';
    }
}

it('keeps the filter values across a column toggle', function () {
    Livewire::test(ReportFilterProbe::class)
        ->set('filterGroup', '7')
        ->set('filterCustomer', '42')
        ->set('dateFrom', '2026-01-01')
        ->set('dateTo', '2026-03-31')
        ->call('toggleColumn', 'customer')
        ->assertSet('filterGroup', '7')
        ->assertSet('filterCustomer', '42')
        ->assertSet('dateFrom', '2026-01-01')
        ->assertSet('dateTo', '2026-03-31');
});

it('leaves a cleared date cleared instead of re-seeding it on the next request', function () {
    Livewire::test(ReportFilterProbe::class)
        ->set('dateFrom', '')
        ->set('dateTo', '')
        ->call('toggleColumn', 'customer')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '');
});

it('seeds the current month and the default columns on mount', function () {
    Livewire::test(ReportFilterProbe::class)
        ->assertSet('dateFrom', now()->startOfMonth()->format('Y-m-d'))
        ->assertSet('dateTo', now()->endOfMonth()->format('Y-m-d'))
        ->assertSet('visibleColumns', ['date', 'customer', 'amount']);
});

it('keeps every column hidden once the user turns them all off', function () {
    Livewire::test(ReportFilterProbe::class)
        ->call('toggleColumn', 'date')
        ->call('toggleColumn', 'customer')
        ->call('toggleColumn', 'amount')
        ->call('$refresh')
        ->assertSet('visibleColumns', []);
});
