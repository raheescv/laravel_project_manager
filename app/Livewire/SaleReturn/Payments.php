<?php

namespace App\Livewire\SaleReturn;

use App\Models\SaleReturn;
use Livewire\Component;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithPagination;

    public $search = '';

    public $branch_id = '';

    public $customer_id = '';

    public $limit = 10;

    public $from_date = '';

    public $to_date = '';

    public $sortField = 'accounts.name';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'SaleReturn-Payments-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function openSalesList($name, $account_id)
    {
        $this->dispatch('Open-CustomerPayment-Component', $name, $account_id);
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
        $data = SaleReturn::orderBy($this->sortField, $this->sortDirection)
            ->join('accounts', 'accounts.id', '=', 'sale_returns.account_id')
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('sale_returns.grand_total', 'like', "%{$value}%")
                        ->orWhere('sale_returns.id', 'like', "%{$value}%")
                        ->orWhere('sale_returns.reference_no', 'like', "%{$value}%")
                        ->orWhere('accounts.name', 'like', "%{$value}%");
                });
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->customer_id ?? '', function ($query, $value) {
                return $query->where('account_id', $value);
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->whereDate('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->whereDate('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->where('balance', '>', 0);
        $totalRow = clone $data;
        $data = $data
            ->select('accounts.name', 'sale_returns.account_id')
            ->selectRaw('count(sale_returns.id) as count')
            ->selectRaw('sum(sale_returns.grand_total) as grand_total')
            ->selectRaw('sum(sale_returns.paid) as paid')
            ->selectRaw('sum(sale_returns.balance) as balance')
            ->groupBy('account_id');

        $data = $data->paginate($this->limit);

        $total['grand_total'] = $totalRow->sum('grand_total');
        $total['paid'] = $totalRow->sum('paid');
        $total['balance'] = $totalRow->sum('balance');

        return view('livewire.sale-return.payments', [
            'total' => $total,
            'data' => $data,
        ]);
    }
}
