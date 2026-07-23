<?php

namespace App\Livewire\Account\Customer;

use App\Models\Sale;
use Livewire\Component;

class Sales extends Component
{
    public $account_id;

    public $from_date;

    public $to_date;

    public $limit = 10;

    public $preset = 30;

    public function mount($account_id = null)
    {
        $this->account_id = $account_id;
        $this->applyPreset(30);
    }

    public function applyPreset($days)
    {
        $this->preset = $days;
        if ($days === 'all') {
            $this->from_date = null;
            $this->to_date = null;

            return;
        }
        $this->from_date = date('Y-m-d', strtotime('-'.(int) $days.' days'));
        $this->to_date = date('Y-m-d');
    }

    public function updatedFromDate()
    {
        $this->preset = null;
    }

    public function updatedToDate()
    {
        $this->preset = null;
    }

    protected function baseQuery()
    {
        return Sale::where('account_id', $this->account_id)
            ->when($this->from_date, fn ($q, $value) => $q->where('date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date, fn ($q, $value) => $q->where('date', '<=', date('Y-m-d', strtotime($value))));
    }

    public function render()
    {
        $sales = collect();
        $totals = null;
        if ($this->account_id) {
            $totals = $this->baseQuery()
                ->selectRaw('COUNT(*) AS invoices, SUM(grand_total) AS grand_total, SUM(paid) AS paid, SUM(balance) AS balance')
                ->first();
            $sales = $this->baseQuery()
                ->limit($this->limit)
                ->latest()
                ->get(['id', 'date', 'invoice_no', 'grand_total', 'paid', 'balance', 'rating', 'feedback', 'feedback_type']);
        }

        return view('livewire.account.customer.sales', [
            'sales' => $sales,
            'totals' => $totals,
        ]);
    }
}
