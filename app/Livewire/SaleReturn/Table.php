<?php

namespace App\Livewire\SaleReturn;

use App\Actions\SaleReturn\DeleteAction;
use App\Exports\SaleReturnExport;
use App\Jobs\Export\ExportSaleReturnJob;
use App\Models\Configuration;
use App\Models\SaleReturn;
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

    public $sale_return_visible_column = [];

    public $selectAll = false;

    public $sortField = 'sale_returns.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'SaleReturn-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->sale_return_visible_column = json_decode(Configuration::where('key', 'sale_return_visible_column')->value('value'), true);
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
        if ($value) {
            $this->selected = SaleReturn::latest()
                ->when($this->branch_id ?? '', function ($query, $value) {
                    return $query->where('branch_id', $value);
                })
                ->when($this->customer_id ?? '', function ($query, $value) {
                    return $query->where('account_id', $value);
                })
                ->when($this->status ?? '', function ($query, $value) {
                    return $query->where('status', $value);
                })
                ->when($this->from_date ?? '', function ($query, $value) {
                    return $query->whereDate('date', '>=', date('Y-m-d', strtotime($value)));
                })
                ->when($this->to_date ?? '', function ($query, $value) {
                    return $query->whereDate('date', '<=', date('Y-m-d', strtotime($value)));
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
        $count = SaleReturn::query()
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->customer_id ?? '', function ($query, $value) {
                return $query->where('account_id', $value);
            })
            ->when($this->status ?? '', function ($query, $value) {
                return $query->where('status', $value);
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->whereDate('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->whereDate('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->count();
        if ($count > 2000) {
            ExportSaleReturnJob::dispatch(Auth::user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'SaleReturn_'.now()->timestamp.'.xlsx';

            return Excel::download(new SaleReturnExport(), $exportFileName);
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
        $data = SaleReturn::with('branch')->orderBy($this->sortField, $this->sortDirection)
            ->join('accounts', 'accounts.id', '=', 'sale_returns.account_id')
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('sale_returns.reference_no', 'like', "%{$value}%")
                        ->orWhere('sale_returns.gross_amount', 'like', "%{$value}%")
                        ->orWhere('sale_returns.item_discount', 'like', "%{$value}%")
                        ->orWhere('sale_returns.tax_amount', 'like', "%{$value}%")
                        ->orWhere('sale_returns.total', 'like', "%{$value}%")
                        ->orWhere('sale_returns.grand_total', 'like', "%{$value}%")
                        ->orWhere('sale_returns.other_discount', 'like', "%{$value}%")
                        ->orWhere('sale_returns.paid', 'like', "%{$value}%")
                        ->orWhere('accounts.name', 'like', "%{$value}%");
                });
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->customer_id ?? '', function ($query, $value) {
                return $query->where('account_id', $value);
            })
            ->when($this->status ?? '', function ($query, $value) {
                return $query->where('status', $value);
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->whereDate('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->whereDate('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->select(
                'sale_returns.*',
                'accounts.name',
            )
            ->latest('sale_returns.id');
        $totalRow = clone $data;
        $data = $data->paginate($this->limit);

        $total['gross_amount'] = $totalRow->sum('gross_amount');
        $total['item_discount'] = $totalRow->sum('item_discount');
        $total['tax_amount'] = $totalRow->sum('tax_amount');
        $total['total'] = $totalRow->sum('total');
        $total['other_discount'] = $totalRow->sum('other_discount');
        $total['grand_total'] = $totalRow->sum('grand_total');
        $total['paid'] = $totalRow->sum('paid');
        $total['balance'] = $totalRow->sum('balance');

        return view('livewire.sale-return.table', [
            'total' => $total,
            'data' => $data,
        ]);
    }
}
