<?php

namespace App\Livewire\Report\Customer;

use App\Models\SaleItem;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerSaleItems extends Component
{
    use WithPagination;

    public $customer_id;

    public $nationality;

    public $product_id;

    public $branch_id;

    public $employee_id;

    public $from_date;

    public $to_date;

    public $perPage = 10;

    public $totalAmount = 0;

    public $totalQuantity = 0;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['customerSaleItemsFilterChanged' => 'filterChanged'];

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function filterChanged($from_date, $to_date, $customer_id = null, $product_id = null, $employee_id = null, $branch_id = null, $nationality = null)
    {
        $this->customer_id = $customer_id;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->product_id = $product_id;
        $this->employee_id = $employee_id;
        $this->branch_id = $branch_id;
        $this->nationality = $nationality;
        $this->resetPage();
    }

    public function render()
    {
        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('users', 'sale_items.employee_id', '=', 'users.id')
            ->join('accounts', 'sales.account_id', '=', 'accounts.id')
            ->select([
                'sales.date',
                'sales.invoice_no',
                'accounts.name as customer',
                'accounts.mobile as mobile',
                'products.name as product',
                'users.name as employee',
                'sale_items.sale_id',
                'sale_items.base_unit_quantity',
                'sale_items.total',
            ])
            ->when($this->employee_id, fn ($q, $value) => $q->where('sale_items.employee_id', $value))
            ->when($this->product_id, fn ($q, $value) => $q->where('sale_items.product_id', $value))
            ->when($this->customer_id, fn ($q, $value) => $q->where('sales.account_id', $value))
            ->when($this->nationality, fn ($q, $value) => $q->where('accounts.nationality', $value))
            ->when($this->branch_id, fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))))
            ->where('sales.status', 'completed')
            ->orderBy('sales.date', 'desc');

        // Calculate totals
        $this->totalAmount = $query->sum('sale_items.total');
        $this->totalQuantity = $query->sum('sale_items.base_unit_quantity');

        $items = $query->paginate($this->perPage);

        return view('livewire.report.customer.customer-sale-items', [
            'items' => $items,
        ]);
    }
}
