<?php

namespace App\Livewire\Report;

use App\Models\Sale;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerVisitHistory extends Component
{
    use WithPagination;

    public $customer_id;

    public $perPage = 10;

    public $from_date;

    public $to_date;

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
    }

    public function render()
    {
        $visits = Sale::query()
            ->join('accounts', 'sales.account_id', '=', 'accounts.id')
            ->select('accounts.id', 'accounts.name', 'accounts.mobile')
            ->selectRaw('sum(sales.grand_total) as total')
            ->selectRaw('count(*) as visits')
            ->selectSub(function ($query) {
                $query->from('sales')->select('date')->whereColumn('account_id', 'accounts.id')->orderBy('date', 'asc')->limit(1);
            }, 'first_sale_date')
            ->selectRaw('CASE WHEN (SELECT MIN(date) FROM sales WHERE account_id = accounts.id) BETWEEN ? AND ? THEN true ELSE false END as is_new_customer', [$this->from_date, $this->to_date])->when($this->customer_id, fn ($q) => $q->where('account_id', $this->customer_id))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))))
            ->groupBy('accounts.id', 'accounts.name', 'accounts.mobile')
            ->paginate($this->perPage);

        return view('livewire.report.customer-visit-history', [
            'visits' => $visits,
        ]);
    }
}
