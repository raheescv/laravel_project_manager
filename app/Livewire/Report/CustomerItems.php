<?php

namespace App\Livewire\Report;

use App\Models\SaleItem;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerItems extends Component
{
    use WithPagination;

    public ?int $product_id = null;

    public ?int $customer_id = null;

    public int $perPage = 10;

    public string $from_date;

    public string $to_date;

    protected $listeners = ['customerItemsFilterChanged' => 'filterChanged'];

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function filterChanged($from_date, $to_date, $customer_id = null, $product_id = null)
    {
        $this->customer_id = $customer_id;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->product_id = $product_id;
        $this->resetPage();
    }

    public function render()
    {
        $items = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('accounts', 'sales.account_id', '=', 'accounts.id')
            ->select([
                'accounts.name as customer',
                'products.name as product',
                SaleItem::raw('SUM(sale_items.quantity) as total_quantity'),
                SaleItem::raw('SUM(sale_items.total) as total_amount'),
            ])
            ->when($this->customer_id, fn ($q, $value) => $q->where('sales.account_id', $value))
            ->when($this->product_id, fn ($q, $value) => $q->where('sale_items.product_id', $value))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))))
            ->groupBy('sales.account_id', 'sale_items.product_id')
            ->orderBy('total_quantity', 'desc')
            ->paginate($this->perPage);

        return view('livewire.report.customer-items', [
            'items' => $items,
        ]);
    }
}
