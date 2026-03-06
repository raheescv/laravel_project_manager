<?php

namespace App\Livewire\Report\Customer;

use App\Models\Sale;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerSales extends Component
{
    use WithPagination;

    public $customer_id;

    public $nationality;

    public $branch_id;

    public $from_date;

    public $to_date;

    public $perPage = 10;

    public $totalAmount = 0;

    public $totalDiscount = 0;

    public $totalItems = 0;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['customerSalesFilterChanged' => 'filterChanged'];

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
        $query = Sale::query()
            ->join('accounts', 'sales.account_id', '=', 'accounts.id')
            ->completed()
            ->when($this->branch_id, fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->when($this->customer_id, fn ($q, $value) => $q->where('sales.account_id', $value))
            ->when($this->nationality, fn ($q, $value) => $q->where('accounts.nationality', $value))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))))
            ->select(
                'accounts.name as customer',
                'accounts.mobile',
                'sales.*'
            )
            ->withCount('items')
            ->orderByDesc('date');
        // Calculate totals
        $totals = $query->get();
        $this->totalAmount = $totals->sum('grand_total');
        $this->totalItems = $totals->sum('items_count');
        $this->totalDiscount = $totals->sum('other_discount');

        $sales = $query->paginate($this->perPage);

        return view('livewire.report.customer.customer-sales', [
            'sales' => $sales,
        ]);
    }
}
