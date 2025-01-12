<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\DeleteAction;
use App\Exports\SaleExport;
use App\Jobs\Export\ExportSaleJob;
use App\Models\Configuration;
use App\Models\Sale;
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
    }

    public function delete()
    {
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction)->execute($id, auth()->id());
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
        if ($value) {
            $this->selected = Sale::latest()
                ->when($this->branch_id ?? '', function ($query, $value) {
                    $query->where('branch_id', $value);
                })
                ->when($this->customer_id ?? '', function ($query, $value) {
                    $query->where('account_id', $value);
                })
                ->when($this->status ?? '', function ($query, $value) {
                    $query->where('status', $value);
                })
                ->when($this->from_date ?? '', function ($query, $value) {
                    $query->whereDate('date', '>=', date('Y-m-d', strtotime($value)));
                })
                ->when($this->to_date ?? '', function ($query, $value) {
                    $query->whereDate('date', '<=', date('Y-m-d', strtotime($value)));
                })
                ->limit(2000)
                ->pluck('id')
                ->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function export()
    {
        $count = Sale::query()
            ->when($this->branch_id ?? '', function ($query, $value) {
                $query->where('branch_id', $value);
            })
            ->when($this->customer_id ?? '', function ($query, $value) {
                $query->where('account_id', $value);
            })
            ->when($this->status ?? '', function ($query, $value) {
                $query->where('status', $value);
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                $query->whereDate('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                $query->whereDate('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->count();
        if ($count > 2000) {
            ExportSaleJob::dispatch(auth()->user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'Sale_'.now()->timestamp.'.xlsx';

            return Excel::download(new SaleExport, $exportFileName);
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

    public function render()
    {
        $data = Sale::with('branch')->orderBy($this->sortField, $this->sortDirection)
            ->join('accounts', 'accounts.id', '=', 'sales.account_id')
            ->when($this->search ?? '', function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $value = trim($value);
                    $q->where('sales.invoice_no', 'like', "%{$value}%")
                        ->orWhere('sales.gross_amount', 'like', "%{$value}%")
                        ->orWhere('sales.item_discount', 'like', "%{$value}%")
                        ->orWhere('sales.tax_amount', 'like', "%{$value}%")
                        ->orWhere('sales.total', 'like', "%{$value}%")
                        ->orWhere('sales.other_discount', 'like', "%{$value}%")
                        ->orWhere('sales.freight', 'like', "%{$value}%")
                        ->orWhere('sales.paid', 'like', "%{$value}%")
                        ->orWhere('accounts.name', 'like', "%{$value}%");
                });
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                $query->where('branch_id', $value);
            })
            ->when($this->customer_id ?? '', function ($query, $value) {
                $query->where('account_id', $value);
            })
            ->when($this->status ?? '', function ($query, $value) {
                $query->where('status', $value);
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                $query->whereDate('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                $query->whereDate('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->select(
                'sales.*',
                'accounts.name',
            )
            ->latest('sales.id');
        $totalRow = clone $data;
        $data = $data->paginate($this->limit);

        $total['gross_amount'] = $totalRow->sum('gross_amount');
        $total['item_discount'] = $totalRow->sum('item_discount');
        $total['tax_amount'] = $totalRow->sum('tax_amount');
        $total['total'] = $totalRow->sum('total');
        $total['other_discount'] = $totalRow->sum('other_discount');
        $total['freight'] = $totalRow->sum('freight');
        $total['grand_total'] = $totalRow->sum('grand_total');
        $total['paid'] = $totalRow->sum('paid');
        $total['balance'] = $totalRow->sum('balance');

        return view('livewire.sale.table', [
            'total' => $total,
            'data' => $data,
        ]);
    }
}
