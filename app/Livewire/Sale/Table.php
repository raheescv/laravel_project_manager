<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\DeleteAction;
use App\Exports\SaleExport;
use App\Jobs\Export\ExportSaleJob;
use App\Models\Configuration;
use App\Models\Sale;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    /**
     * Sortable header field (as sent by <x-sortable-header>) => SQL column.
     * sortBy() only accepts these keys, so no client value reaches ORDER BY.
     *
     * @var array<string, string>
     */
    public const SORTABLE_COLUMNS = [
        'sales.id' => 'sales.id',
        'id' => 'sales.id',
        'date' => 'sales.date',
        'sales.created_at' => 'sales.created_at',
        'invoice_no' => 'sales.invoice_no',
        'reference_no' => 'sales.reference_no',
        'bill_no' => 'sales.bill_no',
        'sales.source' => 'sales.source',
        'branch_id' => 'sales.branch_id',
        'created_by' => 'sales.created_by',
        'accounts.name' => 'accounts.name',
        'payment_method_name' => 'sales.payment_method_name',
        'gross_amount' => 'sales.gross_amount',
        'item_discount' => 'sales.item_discount',
        'tax_amount' => 'sales.tax_amount',
        'total' => 'sales.total',
        'other_discount' => 'sales.other_discount',
        'freight' => 'sales.freight',
        'grand_total' => 'sales.grand_total',
        'paid' => 'sales.paid',
        'balance' => 'sales.balance',
        'status' => 'sales.status',
    ];

    private const TOTAL_COLUMNS = ['gross_amount', 'item_discount', 'tax_amount', 'total', 'other_discount', 'freight', 'grand_total', 'paid', 'balance'];

    public $search = '';

    public $branch_id = '';

    public $customer_id = '';

    public $payment_method_id = '';

    public $sale_type = '';

    public $source = '';

    public $created_by = '';

    public $default_status = '';

    public $from_date = '';

    public $to_date = '';

    // Which timestamp the from/to range runs on: 'date' (invoice date) or 'created_at'.
    public $based_on = 'date';

    public $status = 'draft';

    public $limit = 50;

    public $selected = [];

    public $sale_visible_column = [];

    public $selectAll = false;

    public $sortField = 'sales.id';

    public $sortDirection = 'desc';

    /**
     * Footer sums plus the row count for the current filters. Recomputed only
     * when the filters change (or on refresh/delete), so ticking a row,
     * sorting or paging doesn't re-aggregate the whole filtered set.
     *
     * @var array<string, float|int>
     */
    #[Locked]
    public array $totals = [];

    #[Locked]
    public string $totalsKey = '';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Sale-Refresh-Component' => 'refreshTotals',
    ];

    public function mount()
    {
        // Same merge as the toggle panel, so a column added after this tenant
        // saved their preferences still has a key here for the table to read.
        $this->sale_visible_column = array_merge(
            ColumnVisibility::defaultColumns(),
            json_decode((string) Configuration::where('key', 'sale_visible_column')->value('value'), true) ?: [],
        );
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
        $this->status = Configuration::where('key', 'default_status')->value('value');
        $this->branch_id = session('branch_id');
    }

    public function delete()
    {
        abort_unless(auth()->user()?->can('sale.delete'), 403);
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id, Auth::id());
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            DB::commit();
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];

            $this->selectAll = false;
            $this->refreshTotals();
            $this->dispatch('RefreshSaleTable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['selectAll', 'selected']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    /**
     * Shift + click on a row checkbox sends the whole run of rows between the
     * previous click and this one, in the order they are painted on screen.
     */
    public function toggleRange(array $ids, bool $checked = true)
    {
        $ids = array_map('strval', $ids);
        $selected = array_map('strval', $this->selected);

        $this->selected = $checked
            ? array_values(array_unique(array_merge($selected, $ids)))
            : array_values(array_diff($selected, $ids));
    }

    public function updatedSelectAll($value)
    {
        $this->selected = $value ? $this->getBaseQuery()->select('sales.id')->limit(2000)->pluck('sales.id')->toArray() : [];
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('sale.export'), 403);
        $filters = [
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'source' => $this->source,
            'status' => $this->status,
            'based_on' => $this->based_on,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
        ];
        $count = $this->getBaseQuery()->count();
        if ($count > 2000) {
            ExportSaleJob::dispatch(Auth::user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'Sale_'.now()->timestamp.'.xlsx';

            return Excel::download(new SaleExport($filters), $exportFileName);
        }
    }

    public function refreshTotals(): void
    {
        $this->totalsKey = '';
    }

    public function sortBy($field)
    {
        if (! array_key_exists($field, self::SORTABLE_COLUMNS)) {
            $field = 'sales.id';
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        return [
            'search' => $this->search,
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'sale_type' => $this->sale_type,
            'source' => $this->source,
            'created_by' => $this->created_by,
            'payment_method_id' => $this->payment_method_id,
            'status' => $this->status,
            'based_on' => $this->based_on,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
        ];
    }

    protected function getBaseQuery()
    {
        return Sale::with(['branch:id,name', 'createdUser:id,name'])
            ->join('accounts', 'accounts.id', '=', 'sales.account_id')
            ->filter($this->filters());
    }

    /**
     * One aggregate pass for both the footer sums and the paginator's total.
     *
     * @return array<string, float|int>
     */
    protected function totals(): array
    {
        $key = md5(json_encode($this->filters()));
        if ($this->totalsKey === $key && $this->totals) {
            return $this->totals;
        }

        $sql = collect(self::TOTAL_COLUMNS)->map(fn ($column) => "SUM(sales.{$column}) as {$column}")
            ->push('COUNT(*) as total_count')
            ->implode(', ');
        $row = $this->getBaseQuery()->toBase()->selectRaw($sql)->first();

        $this->totals = collect(self::TOTAL_COLUMNS)
            ->mapWithKeys(fn ($column) => [$column => (float) ($row->{$column} ?? 0)])
            ->put('total_count', (int) ($row->total_count ?? 0))
            ->all();
        $this->totalsKey = $key;

        return $this->totals;
    }

    public function render()
    {
        $total = $this->totals();
        $limit = max(1, (int) $this->limit);
        $page = $this->getPage();
        $sortColumn = self::SORTABLE_COLUMNS[$this->sortField] ?? 'sales.id';
        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        $rows = $this->getBaseQuery()
            ->select('sales.*', 'accounts.name')
            ->orderBy($sortColumn, $sortDirection)
            ->when($sortColumn !== 'sales.id', fn ($q) => $q->orderBy('sales.id', 'desc'))
            ->forPage($page, $limit)
            ->get();

        return view('livewire.sale.table', [
            'total' => $total,
            'data' => new LengthAwarePaginator($rows, $total['total_count'], $limit, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]),
        ]);
    }
}
