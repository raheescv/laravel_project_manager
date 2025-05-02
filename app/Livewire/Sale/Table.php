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

    public $default_status = '';

    public $from_date = '';

    public $to_date = '';

    public $status = 'draft';

    public $limit = 10;

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
        $this->selected = $value ? $this->getBaseQuery()->limit(2000)->pluck('id')->toArray() : [];
    }

    public function export()
    {
        $count = $this->getBaseQuery()->count();
        if ($count > 2000) {
            ExportSaleJob::dispatch(Auth::user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'Sale_'.now()->timestamp.'.xlsx';

            return Excel::download(new SaleExport(), $exportFileName);
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
        return Sale::with('branch')
            ->join('accounts', 'accounts.id', '=', 'sales.account_id')
            ->leftJoin('sale_payments', function ($join) {
                $join->on('sales.id', '=', 'sale_payments.sale_id')
                    ->whereRaw('sale_payments.id = (SELECT id FROM sale_payments sp WHERE sp.sale_id = sales.id ORDER BY sp.created_at ASC LIMIT 1)');
            })
            ->join('accounts as payment_method', 'payment_method.id', '=', 'sale_payments.payment_method_id')
            ->select('sales.*', 'accounts.name', 'payment_method.name as payment_method_name')
            ->filter([
                'search' => $this->search,
                'branch_id' => $this->branch_id,
                'customer_id' => $this->customer_id,
                'status' => $this->status,
                'from_date' => $this->from_date,
                'to_date' => $this->to_date,
            ])
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function render()
    {
        $query = $this->getBaseQuery();
        $totalRow = clone $query;

        $total = [
            'gross_amount' => $totalRow->sum('gross_amount'),
            'item_discount' => $totalRow->sum('item_discount'),
            'tax_amount' => $totalRow->sum('tax_amount'),
            'total' => $totalRow->sum('total'),
            'other_discount' => $totalRow->sum('other_discount'),
            'freight' => $totalRow->sum('freight'),
            'grand_total' => $totalRow->sum('grand_total'),
            'paid' => $totalRow->sum('paid'),
            'balance' => $totalRow->sum('balance'),
        ];

        return view('livewire.sale.table', [
            'total' => $total,
            'data' => $query->paginate($this->limit),
        ]);
    }
}
