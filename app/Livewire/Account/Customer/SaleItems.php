<?php

namespace App\Livewire\Account\Customer;

use App\Models\SaleItem;
use Livewire\Component;

class SaleItems extends Component
{
    public $account_id;

    public $from_date;

    public $to_date;

    public $limit = 10;

    public $preset = 30;

    public $search = '';

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

    /**
     * Joined to sales rather than whereHas() so MySQL can drive the query from
     * sales_tenant_account_date_index instead of scanning every sale_items row
     * belonging to the tenant. Dates compare against the raw DATE column (no
     * whereDate(), which would wrap it in DATE() and disable the index).
     */
    protected function baseQuery()
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.account_id', $this->account_id)
            ->when($this->from_date, fn ($q, $value) => $q->where('sales.date', '>=', date('Y-m-d', strtotime($value))))
            ->when($this->to_date, fn ($q, $value) => $q->where('sales.date', '<=', date('Y-m-d', strtotime($value))))
            ->when($this->search, fn ($q, $value) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', '%'.$value.'%')));
    }

    public function render()
    {
        $sale_items = collect();
        $total_lines = 0;
        if ($this->account_id) {
            $total_lines = $this->baseQuery()->count();
            $sale_items = $this->baseQuery()
                ->select('sale_items.*')
                // other_discount + total are required by SaleItem::getEffectiveTotalAttribute().
                ->with(['sale:id,date,invoice_no,other_discount,total', 'employee:id,name', 'product:id,name', 'unit:id,name'])
                ->orderByDesc('sales.date')
                ->orderByDesc('sale_items.id')
                ->limit($this->limit)
                ->get();
        }

        return view('livewire.account.customer.sale-items', [
            'sale_items' => $sale_items,
            'total_lines' => $total_lines,
        ]);
    }
}
