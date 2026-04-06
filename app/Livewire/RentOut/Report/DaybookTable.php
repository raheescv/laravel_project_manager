<?php

namespace App\Livewire\RentOut\Report;

use App\Exports\RentOut\ServiceDaybookExport;
use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\Account;
use App\Models\RentOutTransaction;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class DaybookTable extends Component
{
    use HasRentOutReportFilters, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $filterCategory = '';

    public $filterSource = ''; // dynamic from DB

    public $filterGroupCol = ''; // RentOutTransaction.group (collection/refund/etc.)

    public $filterDirection = ''; // '', 'charge', 'payment'

    public $filterPaymentType = '';

    public function getDefaultColumns(): array
    {
        return ['date', 'voucher', 'customer', 'group', 'building', 'property', 'category', 'source', 'group_col', 'payment_type', 'remark', 'charge', 'paid', 'balance'];
    }

    protected function baseTransactionQuery(): Builder
    {
        return RentOutTransaction::query()
            ->tap(fn ($q) => $this->applyRentOutFilters($q))
            ->tap(fn ($q) => $this->applyDateFilter($q, 'date'))
            ->when($this->filterCategory, fn ($q, $v) => $q->where('category', $v))
            ->when($this->filterSource, fn ($q, $v) => $q->where('source', $v))
            ->when($this->filterGroupCol, fn ($q, $v) => $q->where('group', $v))
            ->when($this->filterPaymentType, fn ($q, $v) => $q->where('payment_type', $v))
            ->when($this->filterDirection, function ($q, $value) {
                return match ($value) {
                    'charge' => $q->where('debit', '>', 0),
                    'payment' => $q->where('credit', '>', 0),
                    default => $q,
                };
            });
    }

    protected function buildQuery(): Builder
    {
        return $this->baseTransactionQuery()
            ->with(['rentOut.customer', 'rentOut.property', 'rentOut.building', 'rentOut.group', 'rentOut.type', 'account'])
            ->tap(fn ($q) => $this->applySearch($q))
            ->orderBy(
                $this->sortField === 'id' ? 'rent_out_transactions.id' : $this->sortField,
                $this->sortDirection
            );
    }

    /**
     * Top-level KPIs displayed above filters.
     */
    public function getKpisProperty(): array
    {
        $totals = (clone $this->baseTransactionQuery())
            ->selectRaw('COALESCE(SUM(debit), 0) as charge, COALESCE(SUM(credit), 0) as paid, COUNT(*) as txns')
            ->first();

        $charge = (float) ($totals->charge ?? 0);
        $paid = (float) ($totals->paid ?? 0);
        $balance = $charge - $paid;
        $collectionRate = $charge > 0 ? round(($paid / $charge) * 100, 1) : 0;

        $customerCount = (clone $this->baseTransactionQuery())
            ->whereHas('rentOut', fn ($r) => $r->whereNotNull('account_id'))
            ->distinct('rent_out_id')
            ->count('rent_out_id');

        return [
            'total_charge' => $charge,
            'total_paid' => $paid,
            'total_balance' => $balance,
            'collection_rate' => $collectionRate,
            'transactions' => (int) ($totals->txns ?? 0),
            'customers' => $customerCount,
        ];
    }

    /**
     * Source-wise summary table (Rent, Service, Utility, Security, etc.).
     */
    public function getSummaryProperty(): array
    {
        $rows = (clone $this->baseTransactionQuery())
            ->selectRaw('source, COALESCE(SUM(debit), 0) as charge, COALESCE(SUM(credit), 0) as paid, COUNT(*) as txns')
            ->groupBy('source')
            ->get();

        $summary = [];
        foreach ($rows as $row) {
            $charge = (float) $row->charge;
            $paid = (float) $row->paid;
            $summary[] = [
                'name' => $row->source ?: 'Uncategorised',
                'charge' => $charge,
                'paid' => $paid,
                'balance' => $charge - $paid,
                'txns' => (int) $row->txns,
            ];
        }

        usort($summary, fn ($a, $b) => $b['charge'] <=> $a['charge']);

        return $summary;
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
            'filterGroupCol' => $this->filterGroupCol,
            'filterDirection' => $this->filterDirection,
            'filterPaymentType' => $this->filterPaymentType,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'search' => $this->search,
        ];

        return Excel::download(
            new ServiceDaybookExport($filters),
            'rentout-service-daybook-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function resetFilters(): void
    {
        $this->filterCategory = '';
        $this->filterSource = '';
        $this->filterGroupCol = '';
        $this->filterDirection = '';
        $this->filterPaymentType = '';
        parent::resetFilters();
        $this->js("
            ['daybook_filterGroup', 'daybook_filterBuilding', 'daybook_filterProperty', 'daybook_filterCustomer'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.tomSelect) { el.tomSelect.clear(); }
            });
        ");
    }

    public function render()
    {
        $categoryIds = RentOutTransaction::query()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();

        $categories = Account::whereIn('id', $categoryIds)->orderBy('name')->pluck('name', 'id');

        $sources = RentOutTransaction::query()
            ->whereNotNull('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        $groupCols = RentOutTransaction::query()
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        $paymentTypes = RentOutTransaction::query()
            ->whereNotNull('payment_type')
            ->distinct()
            ->orderBy('payment_type')
            ->pluck('payment_type');

        return view('livewire.rent-out.report.daybook-table', [
            'data' => $this->buildQuery()->paginate($this->limit),
            'kpis' => $this->kpis,
            'summary' => $this->summary,
            'categories' => $categories,
            'sources' => $sources,
            'groupCols' => $groupCols,
            'paymentTypes' => $paymentTypes,
            ...$this->getFilterData(),
        ]);
    }
}
