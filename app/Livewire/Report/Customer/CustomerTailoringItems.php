<?php

namespace App\Livewire\Report\Customer;

use App\Models\TailoringOrderItem;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerTailoringItems extends Component
{
    use WithPagination;

    public $customer_id;

    public $nationality;

    public $branch_id;

    public $from_date;

    public $to_date;

    public $perPage = 10;

    public $totalQuantity = 0;

    public $totalAmount = 0;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['customerTailoringItemsFilterChanged' => 'filterChanged'];

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
        $query = TailoringOrderItem::query()
            ->join('tailoring_orders', 'tailoring_order_items.tailoring_order_id', '=', 'tailoring_orders.id')
            ->leftJoin('accounts', 'tailoring_orders.account_id', '=', 'accounts.id')
            ->select([
                'tailoring_order_items.*',
                'tailoring_orders.id as tailoring_order_id',
                'tailoring_orders.order_no',
                'tailoring_orders.order_date',
                'tailoring_orders.customer_name',
                'tailoring_orders.customer_mobile',
                'accounts.name as account_name',
                'accounts.mobile as account_mobile',
                'accounts.nationality as account_nationality',
            ])
            ->when($this->branch_id, fn ($q, $value) => $q->where('tailoring_orders.branch_id', $value))
            ->when($this->customer_id, fn ($q, $value) => $q->where('tailoring_orders.account_id', $value))
            ->when($this->nationality, fn ($q, $value) => $q->where('accounts.nationality', $value))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('tailoring_orders.order_date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('tailoring_orders.order_date', '<=', date('Y-m-d', strtotime($value))));

        $totals = (clone $query)->get();
        $this->totalQuantity = $totals->sum('quantity');
        $this->totalAmount = $totals->sum('total');

        $items = $query
            ->orderByDesc('tailoring_orders.order_date')
            ->orderByDesc('tailoring_order_items.id')
            ->paginate($this->perPage);

        return view('livewire.report.customer.customer-tailoring-items', [
            'items' => $items,
        ]);
    }
}
