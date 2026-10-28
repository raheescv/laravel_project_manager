<?php

namespace App\Livewire\RentOut\Report;

use App\Actions\RentOut\Report\GetRoiReportRowsAction;
use App\Exports\RentOut\RoiExport;
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
 * Sale (lease) ROI report.
 *
 * Rows are one per agreement, like the service charge report: the ROI lines are
 * aggregated, the payouts folded in, and the balance still payable to the buyer
 * shown alongside. Each row expands to the individual ROI lines behind it.
 */
class RoiTable extends Component
{
    use HasColumnPreferences, HasRentOutReportFilters, WithPagination {
        HasRentOutReportFilters::resetFilters as protected resetBaseFilters;
    }

    protected $paginationTheme = 'bootstrap';

    /** '', 'paid', 'partial', 'unpaid' */
    public $filterStatus = '';

    public function mount(): void
    {
        // ROI is booked for annual periods, so open on the current year.
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
            'sale_price' => true,
            'per_month_roi' => true,
            'lines' => true,
            'amount' => true,
            'paid' => true,
            'balance' => true,
            'status' => true,
            'remark' => true,
        ];
    }

    protected function columnPreferenceKey(): string
    {
        return 'rent-out.report.roi.columns';
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
            'date' => 'Last Booked',
            'customer' => 'Customer',
            'group' => 'Group',
            'building' => 'Building',
            'property' => 'Property No',
            'type' => 'Property Type',
            'ownership' => 'Ownership',
            'start_date' => 'Period From',
            'end_date' => 'Period To',
            'no_of_months' => 'Months',
            'sale_price' => 'Sale Price',
            'per_month_roi' => 'Per Month ROI',
            'lines' => 'Entries',
            'amount' => 'ROI',
            'paid' => 'Paid',
            'balance' => 'Payable',
            'status' => 'Status',
            'remark' => 'Remark',
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
            'start_date', 'end_date', 'no_of_months', 'sale_price', 'per_month_roi', 'lines',
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

    protected function action(): GetRoiReportRowsAction
    {
        return new GetRoiReportRowsAction();
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
            new RoiExport($this->filters()),
            'sale-roi-report-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function resetFilters(): void
    {
        $this->filterStatus = '';
        $this->resetBaseFilters();
        $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfYear()->format('Y-m-d');
        $this->js("
            ['roi_filterGroup', 'roi_filterBuilding', 'roi_filterProperty', 'roi_filterCustomer'].forEach(id => {
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

        return view('livewire.rent-out.report.roi-table', [
            'data' => $data,
            'lines' => $action->lines($filters, $data->getCollection()->pluck('rent_out_id')->all()),
            'kpis' => $action->totals($filters),
            'summary' => $action->summaryByGroup($filters),
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
