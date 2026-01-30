<?php

namespace App\Livewire\Purchase;

use App\Models\Purchase;
use Livewire\Component;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithPagination;

    public $search = '';

    public $branch_id = '';

    public $vendor_id = '';

    public $limit = 50;

    public $from_date = '';

    public $to_date = '';

    public $sortField = 'accounts.name';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Purchase-Payments-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
        $this->branch_id = session('branch_id');
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function openPurchasesList($name, $account_id)
    {
        $this->dispatch('Open-VendorPayment-Component', $name, $account_id);
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
        $data = Purchase::orderBy($this->sortField, $this->sortDirection)
            ->join('accounts', 'accounts.id', '=', 'purchases.account_id')
            ->when($this->search ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('purchases.grand_total', 'like', "%{$value}%")
                        ->orWhere('purchases.invoice_no', 'like', "%{$value}%")
                        ->orWhere('accounts.name', 'like', "%{$value}%");
                });
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->vendor_id ?? '', function ($query, $value) {
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
            ->select('accounts.name', 'purchases.account_id')
            ->selectRaw('count(purchases.id) as count')
            ->selectRaw('sum(purchases.grand_total) as grand_total')
            ->selectRaw('sum(purchases.paid) as paid')
            ->selectRaw('sum(purchases.balance) as balance')
            ->groupBy('account_id');

        $data = $data->paginate($this->limit);

        $total['grand_total'] = $totalRow->sum('grand_total');
        $total['paid'] = $totalRow->sum('paid');
        $total['balance'] = $totalRow->sum('balance');

        return view('livewire.purchase.payments', [
            'total' => $total,
            'data' => $data,
        ]);
    }
}
