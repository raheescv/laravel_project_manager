<?php

namespace App\Livewire\Report\Customer;

use App\Models\TailoringOrder;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerTailoring extends Component
{
    use WithPagination;

    public $customer_id;

    public $nationality;

    public $branch_id;

    public $from_date;

    public $to_date;

    public $perPage = 10;

    public $totalOrders = 0;

    public $totalItems = 0;

    public $totalAmount = 0;

    public $totalDiscount = 0;

    public $totalPaid = 0;

    public $totalBalance = 0;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['customerTailoringFilterChanged' => 'filterChanged'];

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function filterChanged($from_date, $to_date, $customer_id = null, $branch_id = null, $nationality = null)
    {
        $this->customer_id = $customer_id;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->branch_id = $branch_id;
        $this->nationality = $nationality;
        $this->resetPage();
    }

    public function render()
    {
        $query = TailoringOrder::query()
            ->with('account:id,name,mobile,nationality')
            ->when($this->branch_id, fn ($q, $value) => $q->where('branch_id', $value))
            ->when($this->customer_id, fn ($q, $value) => $q->where('account_id', $value))
            ->when($this->nationality, function ($q, $value) {
                return $q->whereHas('account', fn ($account) => $account->where('nationality', $value));
            })
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('order_date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('order_date', '<=', date('Y-m-d', strtotime($value))));

        $totals = (clone $query)
            ->withCount('items')
            ->get();

        $this->totalOrders = $totals->count();
        $this->totalItems = $totals->sum('items_count');
        $this->totalAmount = $totals->sum('grand_total');
        $this->totalDiscount = $totals->sum('other_discount');
        $this->totalPaid = $totals->sum('paid');
        $this->totalBalance = $totals->sum('balance');

        $orders = $query
            ->withCount('items')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.report.customer.customer-tailoring', [
            'orders' => $orders,
        ]);
    }
}
