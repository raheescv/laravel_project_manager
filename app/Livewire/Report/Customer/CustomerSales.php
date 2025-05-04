<?php

namespace App\Livewire\Report\Customer;

use App\Models\Sale;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerSales extends Component
{
    use WithPagination;

    public $customer_id;

    public $from_date;

    public $to_date;

    public $perPage = 10;

    public $totalAmount = 0;

    public $totalItems = 0;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['customerVisitHistoryFilterChanged' => 'filterChanged'];

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function filterChanged($from_date, $to_date, $customer_id = null)
    {
        $this->customer_id = $customer_id;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->resetPage();
    }

    public function render()
    {
        $query = Sale::query()
            ->with('account:id,name,mobile')
            ->select([
                'sales.*',
                Sale::raw('(SELECT COUNT(*) FROM sale_items WHERE sale_items.sale_id = sales.id) as items_count'),
            ])
            ->completed()
            ->when($this->customer_id, fn ($q) => $q->where('sales.account_id', $this->customer_id))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))))
            ->orderByDesc('date');

        // Calculate totals
        $totals = $query->get();
        $this->totalAmount = $totals->sum('grand_total');
        $this->totalItems = $totals->sum('items_count');

        $sales = $query->paginate($this->perPage);

        return view('livewire.report.customer.customer-sales', [
            'sales' => $sales,
        ]);
    }
}
