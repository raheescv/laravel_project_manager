<?php

namespace App\Livewire\Tailoring;

use App\Models\Configuration;
use App\Models\TailoringOrder;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $branch_id = '';

    public $customer_id = '';

    public $status = '';

    public $payment_status = '';

    public $date_type = 'order_date';

    public $from_date = '';

    public $to_date = '';

    public $limit = 10;

    public $selected = [];

    public $tailoring_visible_column = [];

    public $selectAll = false;

    public $sortField = 'tailoring_orders.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Tailoring-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->tailoring_visible_column = json_decode(Configuration::where('key', 'tailoring_visible_column')->value('value'), true) ?? [
            'details' => true,
            'customer' => true,
            'status' => true,
            'grand_total' => true,
            'paid' => true,
            'balance' => true,
            'actions' => true,
        ];
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
        $this->branch_id = session('branch_id');
    }

    public function updated($key, $value)
    {
        // Save column visibility to Configuration when it changes
        if (str_starts_with($key, 'tailoring_visible_column.')) {
            Configuration::updateOrCreate(
                ['key' => 'tailoring_visible_column'],
                ['value' => json_encode($this->tailoring_visible_column)]
            );
        } elseif (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedTailoringVisibleColumn($value)
    {
        Configuration::updateOrCreate(
            ['key' => 'tailoring_visible_column'],
            ['value' => json_encode($this->tailoring_visible_column)]
        );
    }

    public function updatedSelectAll($value)
    {
        $this->selected = $value ? $this->getBaseQuery()->select('tailoring_orders.id')->limit(2000)->pluck('tailoring_orders.id')->toArray() : [];
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

    protected function getBaseQuery()
    {
        $filters = [
            'search' => $this->search,
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'date_type' => $this->date_type,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
        ];

        return TailoringOrder::with(['account:id,name', 'salesman:id,name'])
            ->filter($filters);
    }

    public function getColumnDefinitions()
    {
        return [
            'details' => 'Order Details',
            'customer' => 'Customer',
            'status' => 'Status',
            'grand_total' => 'Grand Total',
            'paid' => 'Paid',
            'balance' => 'Balance',
            'actions' => 'Actions',
        ];
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

        return view('livewire.tailoring.table', [
            'total' => $total,
            'columnDefinitions' => $this->getColumnDefinitions(),
            'data' => $query->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->limit),
        ]);
    }
}
