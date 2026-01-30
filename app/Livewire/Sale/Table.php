<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\DeleteAction;
use App\Exports\SaleExport;
use App\Jobs\Export\ExportSaleJob;
use App\Models\Configuration;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $branch_id = '';

    public $customer_id = '';

    public $payment_method_id = '';

    public $sale_type = '';

    public $created_by = '';

    public $default_status = '';

    public $from_date = '';

    public $to_date = '';

    public $status = 'draft';

    public $limit = 50;

    public $selected = [];

    public $sale_visible_column = [];

    public $selectAll = false;

    public $sortField = 'sales.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Sale-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->sale_visible_column = json_decode(Configuration::where('key', 'sale_visible_column')->value('value'), true);
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
        $this->status = Configuration::where('key', 'default_status')->value('value');
        $this->branch_id = session('branch_id');
    }

    public function delete()
    {
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
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            DB::commit();
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];

            $this->selectAll = false;
            $this->dispatch('RefreshSaleTable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value)
    {
        $this->selected = $value ? $this->getBaseQuery()->select('sales.id')->limit(2000)->pluck('sales.id')->toArray() : [];
    }

    public function export()
    {
        $filters = [
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status,
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

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    protected function getBaseQuery()
    {
        $filters = [
            'search' => $this->search,
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'sale_type' => $this->sale_type,
            'created_by' => $this->created_by,
            'payment_method_id' => $this->payment_method_id,
            'status' => $this->status,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
        ];

        return Sale::with('branch')
            ->join('accounts', 'accounts.id', '=', 'sales.account_id')
            ->filter($filters);

    }

    public function render()
    {
        $query = $this->getBaseQuery();
        $totals = clone $query;

        $sql = '
            SUM(gross_amount) as gross_amount,
            SUM(item_discount) as item_discount,
            SUM(tax_amount) as tax_amount,
            SUM(total) as total,
            SUM(other_discount) as other_discount,
            SUM(freight) as freight,
            SUM(grand_total) as grand_total,
            SUM(paid) as paid,
            SUM(balance) as balance
        ';
        $total = $totals->selectRaw($sql)
            // ->useIndex('sales_tenant_id_invoice_no_unique')
            ->first();

        $total = [
            'gross_amount' => $total->gross_amount ?? 0,
            'item_discount' => $total->item_discount ?? 0,
            'tax_amount' => $total->tax_amount ?? 0,
            'total' => $total->total ?? 0,
            'other_discount' => $total->other_discount ?? 0,
            'freight' => $total->freight ?? 0,
            'grand_total' => $total->grand_total ?? 0,
            'paid' => $total->paid ?? 0,
            'balance' => $total->balance ?? 0,
        ];

        return view('livewire.sale.table', [
            'total' => $total,
            'data' => $query->select('sales.*', 'accounts.name')
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->limit),
        ]);
    }
}
