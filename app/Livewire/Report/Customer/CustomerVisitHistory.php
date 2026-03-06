<?php

namespace App\Livewire\Report\Customer;

use App\Models\Sale;
use App\Models\TailoringOrder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerVisitHistory extends Component
{
    use WithPagination;

    public $customer_id;

    public $nationality;

    public $perPage = 10;

    public $from_date;

    public $to_date;

    public $totalCustomers = 0;

    public $newCustomers = 0;

    public $existingCustomers = 0;

    public $branch_id;

    protected $listeners = ['customerVisitHistoryFilterChanged' => 'filterChanged'];

    protected $paginationTheme = 'bootstrap';

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
        $salesActivity = Sale::query()
            ->when($this->branch_id, fn ($q, $value) => $q->where('sales.branch_id', $value))
            ->when($this->customer_id, fn ($q, $value) => $q->where('sales.account_id', $value))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))))
            ->completed()
            ->whereNotNull('sales.account_id')
            ->selectRaw('sales.account_id as account_id')
            ->selectRaw('COUNT(*) as sale_visits')
            ->selectRaw('SUM(sales.grand_total) as sale_total')
            ->selectRaw('0 as tailoring_visits')
            ->selectRaw('0 as tailoring_total')
            ->selectRaw('MIN(sales.date) as first_visit_date')
            ->groupBy('sales.account_id');

        $tailoringActivity = TailoringOrder::query()
            ->when($this->branch_id, fn ($q, $value) => $q->where('tailoring_orders.branch_id', $value))
            ->when($this->customer_id, fn ($q, $value) => $q->where('tailoring_orders.account_id', $value))
            ->when($this->from_date ?? '', fn ($q, $value) => $q->whereDate('tailoring_orders.order_date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date ?? '', fn ($q, $value) => $q->whereDate('tailoring_orders.order_date', '<=', date('Y-m-d', strtotime($value))))
            ->whereNotNull('tailoring_orders.account_id')
            ->selectRaw('tailoring_orders.account_id as account_id')
            ->selectRaw('0 as sale_visits')
            ->selectRaw('0 as sale_total')
            ->selectRaw('COUNT(*) as tailoring_visits')
            ->selectRaw('SUM(tailoring_orders.grand_total) as tailoring_total')
            ->selectRaw('MIN(tailoring_orders.order_date) as first_visit_date')
            ->groupBy('tailoring_orders.account_id');

        $activity = $salesActivity->unionAll($tailoringActivity);

        $query = DB::query()
            ->fromSub($activity, 'activity')
            ->join('accounts', 'accounts.id', '=', 'activity.account_id')
            ->select('accounts.id', 'accounts.name', 'accounts.mobile', 'accounts.nationality')
            ->selectRaw('SUM(activity.sale_total) as sale_total')
            ->selectRaw('SUM(activity.tailoring_total) as tailoring_total')
            ->selectRaw('SUM(activity.sale_visits) as sale_visits')
            ->selectRaw('SUM(activity.tailoring_visits) as tailoring_visits')
            ->selectRaw('(SUM(activity.sale_total) + SUM(activity.tailoring_total)) as total')
            ->selectRaw('(SUM(activity.sale_visits) + SUM(activity.tailoring_visits)) as visits')
            ->selectRaw('MIN(activity.first_visit_date) as first_visit_date')
            ->when($this->nationality, fn ($q, $value) => $q->where('accounts.nationality', $value))
            ->groupBy('accounts.id', 'accounts.name', 'accounts.mobile', 'accounts.nationality')
            ->orderByRaw('visits DESC');

        // Calculate statistics
        $statistics = clone $query;
        $statistics = collect($statistics->get());
        $this->totalCustomers = $statistics->count();
        $this->newCustomers = $statistics->filter(function ($row) {
            if (! $row->first_visit_date) {
                return false;
            }

            return $row->first_visit_date >= $this->from_date && $row->first_visit_date <= $this->to_date;
        })->count();
        $this->existingCustomers = $this->totalCustomers - $this->newCustomers;

        $visits = $query->paginate($this->perPage);

        return view('livewire.report.customer.customer-visit-history', [
            'visits' => $visits,
            'totalCustomers' => $this->totalCustomers,
            'newCustomers' => $this->newCustomers,
            'existingCustomers' => $this->existingCustomers,
        ]);
    }
}
