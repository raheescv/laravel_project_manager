<?php

namespace App\Livewire\Tailoring;

use App\Models\TailoringOrder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Receipts extends Component
{
    use WithPagination;

    public $search = '';

    public $branch_id = '';

    public $customer_id = '';

    public $limit = 10;

    public $from_date = '';

    public $to_date = '';

    public $sortField = 'customer_display';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Tailoring-Receipts-Refresh' => '$refresh',
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

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function openReceiptModal($accountId, $customerName, $customerMobile, $displayName)
    {
        $this->dispatch('Open-TailoringCustomerReceipt', [
            'account_id' => $accountId ?: null,
            'customer_name' => $customerName ?? '',
            'customer_mobile' => $customerMobile ?? '',
            'display_name' => $displayName ?? '',
        ]);
    }

    public function render()
    {
        $baseQuery = TailoringOrder::query()
            ->leftJoin('accounts', 'accounts.id', '=', 'tailoring_orders.account_id')
            ->when($this->search !== '', function ($query) {
                $value = trim($this->search);

                return $query->where(function ($q) use ($value) {
                    $q->where('tailoring_orders.order_no', 'like', "%{$value}%")
                        ->orWhere('tailoring_orders.customer_name', 'like', "%{$value}%")
                        ->orWhere('tailoring_orders.customer_mobile', 'like', "%{$value}%")
                        ->orWhere('accounts.name', 'like', "%{$value}%")
                        ->orWhere('accounts.mobile', 'like', "%{$value}%");
                });
            })
            ->when($this->branch_id !== '', fn ($q) => $q->where('tailoring_orders.branch_id', $this->branch_id))
            ->when($this->customer_id !== '', fn ($q) => $q->where('tailoring_orders.account_id', $this->customer_id))
            ->when($this->from_date !== '', fn ($q) => $q->whereDate('tailoring_orders.order_date', '>=', $this->from_date))
            ->when($this->to_date !== '', fn ($q) => $q->whereDate('tailoring_orders.order_date', '<=', $this->to_date))
            ->where('tailoring_orders.balance', '>', 0);

        $totalRow = clone $baseQuery;

        $data = $baseQuery
            ->select(
                'tailoring_orders.account_id',
                'tailoring_orders.customer_name',
                'tailoring_orders.customer_mobile',
                DB::raw('COUNT(tailoring_orders.id) as count'),
                DB::raw('SUM(tailoring_orders.grand_total) as grand_total'),
                DB::raw('SUM(tailoring_orders.paid) as paid'),
                DB::raw('SUM(tailoring_orders.balance) as balance')
            )
            ->groupBy('tailoring_orders.account_id', 'tailoring_orders.customer_name', 'tailoring_orders.customer_mobile');

        $data = $baseQuery->orderBy('tailoring_orders.customer_name', $this->sortDirection)->paginate($this->limit);

        $total = [
            'grand_total' => $totalRow->sum('tailoring_orders.grand_total'),
            'paid' => $totalRow->sum('tailoring_orders.paid'),
            'balance' => $totalRow->sum('tailoring_orders.balance'),
        ];

        return view('livewire.tailoring.receipts', [
            'data' => $data,
            'total' => $total,
        ]);
    }
}
