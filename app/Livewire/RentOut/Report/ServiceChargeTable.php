<?php

namespace App\Livewire\RentOut\Report;

use App\Actions\RentOut\Report\GetServiceChargeReportRowsAction;
use App\Exports\RentOut\ServiceChargeExport;
use App\Livewire\Concerns\HasColumnPreferences;
use App\Livewire\RentOut\Concerns\HasRentOutReportFilters;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Sale (lease) service charge report.
 *
 * Rows are one per agreement rather than one per charge line: the charges are
 * aggregated, the service receipts folded in, and the outstanding balance shown
 * alongside. Each row expands to the individual charge lines behind it.
 */
class ServiceChargeTable extends Component
{
    use HasColumnPreferences, HasRentOutReportFilters, WithPagination {
        HasRentOutReportFilters::resetFilters as protected resetBaseFilters;
    }

    protected $paginationTheme = 'bootstrap';

    /** '', 'paid', 'partial', 'unpaid' */
    public $filterStatus = '';

    public function mount(): void
    {
        // Service charges are raised for annual periods, so a month-wide window
        // would normally come back empty on first load.
        $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfYear()->format('Y-m-d');
        $this->sortField = 'date';
        $this->sortDirection = 'desc';

        $this->initializeColumnPreferences();
    }

    /** @return array<string, bool> */
    protected function defaultColumns(): array
    {
        return [
            'date' => true,
            'customer' => true,
            'group' => true,
            'building' => true,
            'property' => true,
            'type' => false,
            'ownership' => false,
            'start_date' => true,
            'end_date' => true,
            'no_of_months' => true,
            'no_of_days' => false,
            'unit_size' => true,
            'per_square_meter_price' => true,
            'per_day_price' => false,
            'lines' => true,
            'amount' => true,
            'paid' => true,
            'balance' => true,
            'status' => true,
            'remark' => true,
            'reason' => false,
        ];
    }

    protected function columnPreferenceKey(): string
    {
        return 'rent-out.report.service-charge.columns';
    }

    /**
     * Restore the shipped columns, resolving the `resetColumns` both traits declare.
     *
     * Renderless like the per-column toggles: the filter row above the table is
     * TomSelect widgets whose values live in the DOM rather than in the markup,
     * and morphing the component on a column change loses them.
     *
     * @return array<string, bool> the restored defaults
     */
    #[Renderless]
    public function resetColumns(): array
    {
        $this->columns = $this->defaultColumns();
        $this->persistColumns();

        return $this->columns;
    }

    /** @return array<string, string> */
    public function columnLabels(): array
    {
        return [
            'date' => 'Last Charged',
            'customer' => 'Customer',
            'group' => 'Group',
            'building' => 'Building',
            'property' => 'Property No',
            'type' => 'Property Type',
            'ownership' => 'Ownership',
            'start_date' => 'Period From',
            'end_date' => 'Period To',
            'no_of_months' => 'Months',
            'no_of_days' => 'Days',
            'unit_size' => 'Unit Size',
            'per_square_meter_price' => 'Per Sq M Price',
            'per_day_price' => 'Per Day Price',
            'lines' => 'Charges',
            'amount' => 'Charged',
            'paid' => 'Paid',
            'balance' => 'Balance',
            'status' => 'Status',
            'remark' => 'Remark',
            'reason' => 'Reason',
        ];
    }

    /**
     * Columns rendered before `amount`, in table order — the footer mirrors these
     * so the totals stay under their own headings whatever is toggled off.
     *
     * @return array<int, string>
     */
    public function leadingColumns(): array
    {
        return [
            'date', 'customer', 'group', 'building', 'property', 'type', 'ownership',
            'start_date', 'end_date', 'no_of_months', 'no_of_days', 'unit_size',
            'per_square_meter_price', 'per_day_price', 'lines',
        ];
    }

    /**
     * Everything the report query needs, in one shape shared with the export.
     *
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        return [
            'filterGroup' => $this->filterGroup,
            'filterBuilding' => $this->filterBuilding,
            'filterType' => $this->filterType,
            'filterProperty' => $this->filterProperty,
            'filterCustomer' => $this->filterCustomer,
            'filterOwnership' => $this->filterOwnership,
            'filterStatus' => $this->filterStatus,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'search' => $this->search,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ];
    }

    protected function action(): GetServiceChargeReportRowsAction
    {
        return new GetServiceChargeReportRowsAction();
    }

    /**
     * The grouped report query — one row per agreement.
     */
    protected function buildQuery(): Builder
    {
        return $this->action()->query($this->filters());
    }

    public function download()
    {
        abort_unless(auth()->user()?->can('rent out lease.export'), 403);

        return Excel::download(
            new ServiceChargeExport($this->filters()),
            'sale-service-charge-report-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function resetFilters(): void
    {
        $this->filterStatus = '';
        $this->resetBaseFilters();
        $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfYear()->format('Y-m-d');
        $this->js("
            ['svc_filterGroup', 'svc_filterBuilding', 'svc_filterProperty', 'svc_filterCustomer'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.tomSelect) { el.tomSelect.clear(); }
            });
        ");
    }

    public function render()
    {
        $filters = $this->filters();
        $action = $this->action();

        $data = $action->paginate($filters, (int) $this->limit);

        return view('livewire.rent-out.report.service-charge-table', [
            'data' => $data,
            'lines' => $action->lines($filters, $data->getCollection()->pluck('rent_out_id')->all()),
            'kpis' => $action->totals($filters),
            'summary' => $action->summaryByGroup($filters),
            // Ownership is free text on the property, so offer what is actually
            // recorded rather than a hard-coded owned/rented pair that matches nothing.
            'ownerships' => Property::query()
                ->whereNotNull('ownership')
                ->where('ownership', '!=', '')
                ->distinct()
                ->orderBy('ownership')
                ->pluck('ownership'),
            ...$this->getFilterData(),
        ]);
    }
}
