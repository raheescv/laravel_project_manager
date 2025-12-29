<?php

namespace App\Livewire\Sale;

use App\Models\Sale;
use Livewire\Component;
use Livewire\WithPagination;

class BookingReceipts extends Component
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
        'Sale-Receipts-Refresh-Component' => '$refresh',
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
        $this->dispatch('Open-CustomerReceipt-Component', $name, $account_id);
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
        $data = Sale::orderBy($this->sortField, $this->sortDirection)
            ->join('accounts', 'accounts.id', '=', 'sales.account_id')
             ->where('sales.type', 'booking')
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('sales.grand_total', 'like', "%{$value}%")
                        ->orWhere('sales.invoice_no', 'like', "%{$value}%")
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
            ->select('accounts.name', 'sales.account_id')
            ->selectRaw('count(sales.id) as count')
            ->selectRaw('sum(sales.grand_total) as grand_total')
            ->selectRaw('sum(sales.paid) as paid')
            ->selectRaw('sum(sales.balance) as balance')
            ->groupBy('account_id');

        $data = $data->paginate($this->limit);

        $total['grand_total'] = $totalRow->sum('grand_total');
        $total['paid'] = $totalRow->sum('paid');
        $total['balance'] = $totalRow->sum('balance');

        return view('livewire.sale.booking_receipts', [
            'total' => $total,
            'data' => $data,
        ]);
    }
}
