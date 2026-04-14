<?php

namespace App\Livewire\RentOut;

use App\Exports\RentOut\ServiceExport;
use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\Account;
use App\Models\RentOutTransaction;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class ServicePaymentTable extends Component
{
    use HasRentOutReportFilters, WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Service-specific filters
    public $filterCategory = '';

    public $filterSource = ''; // '', 'Service', 'ServiceCharge'

    public $filterDirection = ''; // '', 'charge', 'payment'

    public function mount(): void
    {
        // Default to a wider range (year-to-date) so data shows on first load.
        $this->dateFrom = \Carbon\Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = \Carbon\Carbon::now()->endOfYear()->format('Y-m-d');
    }

    public function getDefaultColumns(): array
    {
        return ['date', 'customer', 'group', 'building', 'property', 'ownership', 'category', 'source', 'remark', 'charge', 'paid', 'balance'];
    }

    protected function getSelectableIds(): array
    {
        return $this->buildQuery()->limit(2000)->pluck('rent_out_transactions.id')->toArray();
    }

    protected function buildQuery(): Builder
    {
        return RentOutTransaction::query()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'account'])
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'date'))
            ->tap(fn ($q) => $this->applySearch($q))
            ->when($this->filterCategory, fn ($q, $v) => $q->where('category', $v))
            ->when($this->filterSource, fn ($q, $v) => $q->where('source', $v))
            ->when($this->filterDirection, function ($q, $value) {
                return match ($value) {
                    'charge' => $q->where('debit', '>', 0),
                    'payment' => $q->where('credit', '>', 0),
                    default => $q,
                };
            })
            ->orderBy($this->sortField === 'id' ? 'rent_out_transactions.id' : $this->sortField, $this->sortDirection);
    }

    /**
     * Summary grouped by service category, with totals row.
     */
    public function getSummaryProperty(): array
    {
        $baseQuery = RentOutTransaction::query()
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'date'))
            ->when($this->filterCategory, fn ($q, $v) => $q->where('category', $v))
            ->when($this->filterSource, fn ($q, $v) => $q->where('source', $v));

        $rows = (clone $baseQuery)
            ->selectRaw('category, COALESCE(SUM(debit), 0) as charge, COALESCE(SUM(credit), 0) as paid')
            ->groupBy('category')
            ->get();

        $categoryIds = $rows->pluck('category')->filter()->unique()->values()->toArray();
        $names = Account::whereIn('id', $categoryIds)->pluck('name', 'id')->toArray();

        $summary = [];
        foreach ($rows as $row) {
            $charge = (float) $row->charge;
            $paid = (float) $row->paid;
            $summary[] = [
                'name' => $names[$row->category] ?? ($row->category ?: 'Uncategorised'),
                'charge' => $charge,
                'paid' => $paid,
                'balance' => $charge - $paid,
            ];
        }

        // Sort by charge desc to highlight biggest service items
        usort($summary, fn ($a, $b) => $b['charge'] <=> $a['charge']);

        $totals = (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(debit), 0) as charge, COALESCE(SUM(credit), 0) as paid')
            ->first();

        $totalCharge = (float) ($totals->charge ?? 0);
        $totalPaid = (float) ($totals->paid ?? 0);

        $summary[] = [
            'name' => 'Total',
            'charge' => $totalCharge,
            'paid' => $totalPaid,
            'balance' => $totalCharge - $totalPaid,
        ];

        return $summary;
    }

    /**
     * Top-level KPIs for the dashboard cards.
     */
    public function getKpisProperty(): array
    {
        $baseQuery = RentOutTransaction::query()
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'date'))
            ->when($this->filterCategory, fn ($q, $v) => $q->where('category', $v))
            ->when($this->filterSource, fn ($q, $v) => $q->where('source', $v));

        $totals = (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(debit), 0) as charge, COALESCE(SUM(credit), 0) as paid, COUNT(*) as txns')
            ->first();

        $charge = (float) ($totals->charge ?? 0);
        $paid = (float) ($totals->paid ?? 0);
        $balance = $charge - $paid;

        $customerCount = (clone $baseQuery)
            ->whereHas('rentOut', fn ($r) => $r->whereNotNull('account_id'))
            ->distinct('rent_out_id')
            ->count('rent_out_id');

        $collectionRate = $charge > 0 ? round(($paid / $charge) * 100, 1) : 0;

        return [
            'total_charge' => $charge,
            'total_paid' => $paid,
            'total_balance' => $balance,
            'collection_rate' => $collectionRate,
            'transactions' => (int) ($totals->txns ?? 0),
            'customers' => $customerCount,
        ];
    }

    public function download()
    {
        $filters = [
            'filterGroup' => $this->filterGroup,
            'filterBuilding' => $this->filterBuilding,
            'filterType' => $this->filterType,
            'filterProperty' => $this->filterProperty,
            'filterCustomer' => $this->filterCustomer,
            'filterOwnership' => $this->filterOwnership,
            'filterCategory' => $this->filterCategory,
            'filterSource' => $this->filterSource,
            'filterDirection' => $this->filterDirection,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'search' => $this->search,
        ];

        return Excel::download(new ServiceExport($filters), 'rentout-service-report-'.now()->format('Y-m-d').'.xlsx');
    }

    public function resetFilters(): void
    {
        $this->filterCategory = '';
        $this->filterSource = '';
        $this->filterDirection = '';
        parent::resetFilters();
        $this->dateFrom = \Carbon\Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = \Carbon\Carbon::now()->endOfYear()->format('Y-m-d');
        $this->js("
            ['service_filterGroup', 'service_filterBuilding', 'service_filterProperty', 'service_filterCustomer'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.tomSelect) { el.tomSelect.clear(); }
            });
        ");
    }

    public function render()
    {
        $categoryIds = RentOutTransaction::query()
            ->whereIn('source', ['Service', 'ServiceCharge'])
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();

        $categories = Account::whereIn('id', $categoryIds)->orderBy('name')->pluck('name', 'id');

        return view('livewire.rent-out.service-payment-table', [
            'data' => $this->buildQuery()->paginate($this->limit),
            'summary' => $this->summary,
            'kpis' => $this->kpis,
            'categories' => $categories,
            ...$this->getFilterData(),
        ]);
    }
}
