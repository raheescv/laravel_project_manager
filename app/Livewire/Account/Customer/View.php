<?php

namespace App\Livewire\Account\Customer;

use App\Models\Account;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class View extends Component
{
    protected $listeners = [
        'Customer-View-Component' => 'view',
    ];

    public $account_id;

    public $total_sales;

    public $sales;

    public $selected_tab = 'Sales';

    public $total_sale_returns;

    public $sale_limit = 10;

    public $sale_return_limit = 10;

    public $sale_item_limit = 10;

    public $sale_returns;

    public $sale_from_date;

    public $sale_to_date;

    public $sale_item_from_date;

    public $sale_item_to_date;

    public $sale_return_from_date;

    public $sale_return_to_date;

    public $sale_items = [];

    public $item_count;

    public $accounts;

    public function view($account_id)
    {
        $this->total_sales = [];
        $this->mount($account_id);
        $this->dispatch('ToggleCustomerViewModal');
    }

    public function mount($account_id = null)
    {
        $this->account_id = $account_id;
        $this->sale_from_date = date('Y-m-d', strtotime('-1 month'));
        $this->sale_to_date = date('Y-m-d');
        $this->sale_item_from_date = date('Y-m-d', strtotime('-1 month'));
        $this->sale_item_to_date = date('Y-m-d');
        $this->sale_return_from_date = date('Y-m-d', strtotime('-1 month'));
        $this->sale_return_to_date = date('Y-m-d');

    }

    public function render()
    {
        if ($this->account_id) {
            $this->accounts = Account::find($this->account_id)->toArray();
            $this->total_sales = DB::table('sales')
                ->where('account_id', $this->account_id)
                ->selectRaw('account_id, SUM(grand_total) AS grand_total, SUM(paid) AS paid, SUM(balance) AS balance')
                ->groupBy('account_id')
                ->first();
            $this->sales = Sale::where('account_id', $this->account_id)
                ->when($this->sale_from_date ?? '', fn ($q, $value) => $q->whereDate('date', '>=', date('Y-m-d', strtotime($value))))
                ->when($this->sale_to_date ?? '', fn ($q, $value) => $q->whereDate('date', '<=', date('Y-m-d', strtotime($value))))
                ->limit($this->sale_limit)
                ->latest()
                ->get(['id', 'date', 'invoice_no', 'grand_total', 'paid', 'balance']);

            $this->total_sale_returns = DB::table('sale_returns')
                ->where('account_id', $this->account_id)
                ->selectRaw('account_id, SUM(grand_total) AS grand_total, SUM(paid) AS paid, SUM(balance) AS balance')
                ->groupBy('account_id')
                ->first();
            $this->sale_returns = SaleReturn::where('account_id', $this->account_id)
                ->when($this->sale_return_from_date ?? '', fn ($q, $value) => $q->whereDate('date', '>=', date('Y-m-d', strtotime($value))))
                ->when($this->sale_return_to_date ?? '', fn ($q, $value) => $q->whereDate('date', '<=', date('Y-m-d', strtotime($value))))
                ->limit($this->sale_return_limit)
                ->latest()
                ->get(['id', 'date', 'reference_no', 'grand_total', 'paid', 'balance']);

            $this->item_count = SaleItem::groupBy('product_id')
                ->whereHas('sale', function ($query) {
                    return $query->where('sales.account_id', $this->account_id);
                })
                ->select('product_id')
                ->selectRaw('count(product_id) as count')
                ->get();
            $this->sale_items = SaleItem::limit($this->sale_item_limit)
                ->whereHas('sale', function ($query) {
                    return $query->where('sales.account_id', $this->account_id)
                        ->when($this->sale_item_from_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '>=', date('Y-m-d', strtotime($value))))
                        ->when($this->sale_item_to_date ?? '', fn ($q, $value) => $q->whereDate('sales.date', '<=', date('Y-m-d', strtotime($value))));
                })
                ->latest()
                ->get();
        }

        return view('livewire.account.customer.view');
    }
}
