<?php

namespace App\Livewire\Account\Customer;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Livewire\Component;

class SaleItemSummary extends Component
{
    public $account_id;

    public function mount($account_id = null)
    {
        $this->account_id = $account_id;
    }

    public function render()
    {
        $rows = collect();
        $highlights = ['products' => 0, 'top' => null, 'first_date' => null, 'last_date' => null, 'invoices' => 0, 'frequency' => 0];

        if ($this->account_id) {
            // Joined to sales (not whereHas) so the query is driven by
            // sales_tenant_account_date_index rather than scanning all sale_items.
            $rows = SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.account_id', $this->account_id)
                ->groupBy('sale_items.product_id')
                ->select('sale_items.product_id')
                ->selectRaw('COUNT(sale_items.product_id) AS count')
                ->selectRaw('SUM(sale_items.base_unit_quantity) AS quantity')
                ->orderByDesc('count')
                ->get();

            $products = Product::whereIn('id', $rows->pluck('product_id')->filter())->pluck('name', 'id');
            $rows->each(fn ($row) => $row->product_name = $products[$row->product_id] ?? '—');

            $span = Sale::where('account_id', $this->account_id)
                ->selectRaw('COUNT(*) AS invoices, MIN(date) AS first_date, MAX(date) AS last_date')
                ->first();

            $months = 0;
            if ($span?->first_date && $span?->last_date) {
                $months = max((float) \Carbon\Carbon::parse($span->first_date)->floatDiffInMonths($span->last_date), 1);
            }

            $highlights = [
                'products' => $rows->count(),
                'top' => $rows->first(),
                'first_date' => $span->first_date ?? null,
                'last_date' => $span->last_date ?? null,
                'invoices' => (int) ($span->invoices ?? 0),
                'frequency' => $months > 0 ? round(((int) ($span->invoices ?? 0)) / $months, 1) : 0,
            ];
        }

        return view('livewire.account.customer.sale-item-summary', [
            'rows' => $rows,
            'highlights' => $highlights,
            'max' => (int) ($rows->max('count') ?: 1),
        ]);
    }
}
