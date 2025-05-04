<?php

namespace App\Livewire\Report\Customer;

use App\Models\Sale;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerLocation extends Component
{
    use WithPagination;

    public $from_date;

    public $to_date;

    public $branch_id;

    public $dataPoints;

    public $customer_id;

    public $nationality;

    public $search = '';

    public $limit = 10;

    protected $listeners = ['CustomerLocationRefreshComponent' => 'filterChanged'];

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function filterChanged($from_date, $to_date, $customer_id = null, $nationality = null)
    {
        $this->customer_id = $customer_id;
        $this->nationality = $nationality;
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->resetPage();
    }

    public function render()
    {
        $query = Sale::query()
            ->join('accounts', 'sales.account_id', '=', 'accounts.id')
            ->when($this->branch_id, fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->when($this->customer_id, fn ($q, $value) => $q->where('account_id', $value))
            ->when($this->nationality, fn ($q, $value) => $q->where('accounts.nationality', $value))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))))
            ->whereNotNull('accounts.nationality')
            ->where('accounts.nationality', '!=', '')
            ->selectRaw('accounts.nationality, COUNT(*) as customer_count')
            ->groupBy('accounts.nationality');
        $total = clone $query;
        $this->dataPoints = [];
        $productList = $total->orderBy('customer_count', 'DESC')->limit(10)->pluck('customer_count', 'nationality')->toArray();
        foreach ($productList as $label => $value) {
            $this->dataPoints[] = [
                'label' => $label,
                'y' => $value,
            ];
        }
        $this->dispatch('updateCustomerLocationPieChart', $this->dataPoints);

        $data = $query->paginate($this->limit);

        return view('livewire.report.customer.customer-location', compact('data'));
    }
}
