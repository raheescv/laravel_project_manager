<?php

namespace App\Livewire\Tailoring;

use App\Models\TailoringOrder;
use Livewire\Component;
use Livewire\WithPagination;

class OrderSearch extends Component
{
    use WithPagination;

    public $customer_id = '';

    public $mobile = '';

    public $order_no = '';

    public $limit = 10;

    public $sortField = 'tailoring_orders.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'customer_id' => ['except' => ''],
        'mobile' => ['except' => ''],
        'order_no' => ['except' => ''],
    ];

    public function updated($key, $value)
    {
        if (! in_array($key, ['customer_id', 'mobile', 'order_no'])) {
            return;
        }
        $this->resetPage();
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

    public function clearFilters()
    {
        $this->customer_id = '';
        $this->mobile = '';
        $this->order_no = '';
        $this->resetPage();
        $this->dispatch('order-search-clear-customer');
    }

    protected function getBaseQuery()
    {
        $query = TailoringOrder::with(['account:id,name', 'salesman:id,name']);

        $hasFilter = trim((string) $this->customer_id) !== ''
            || trim((string) $this->mobile) !== ''
            || trim((string) $this->order_no) !== '';

        if (! $hasFilter) {
            return $query->whereRaw('1 = 0'); // No results until at least one filter is set
        }

        return $query->where(function ($q) {
            if (trim((string) $this->customer_id) !== '') {
                $q->orWhere('tailoring_orders.account_id', $this->customer_id);
            }
            if (trim((string) $this->mobile) !== '') {
                $mobile = '%'.trim($this->mobile).'%';
                $q->orWhere(function ($sub) use ($mobile) {
                    $sub->where('tailoring_orders.customer_mobile', 'like', $mobile)
                        ->orWhereHas('account', fn ($acc) => $acc->where('accounts.mobile', 'like', $mobile));
                });
            }
            if (trim((string) $this->order_no) !== '') {
                $q->orWhere('tailoring_orders.order_no', 'like', '%'.trim($this->order_no).'%');
            }
        });
    }

    public function render()
    {
        $query = $this->getBaseQuery();
        $totals = clone $query;

        $sql = '
            SUM(grand_total) as grand_total,
            SUM(paid) as paid,
            SUM(balance) as balance
        ';
        $total = $totals->selectRaw($sql)->first();

        $total = [
            'grand_total' => $total->grand_total ?? 0,
            'paid' => $total->paid ?? 0,
            'balance' => $total->balance ?? 0,
        ];

        return view('livewire.tailoring.order-search', [
            'total' => $total,
            'data' => $query->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->limit),
        ]);
    }
}
