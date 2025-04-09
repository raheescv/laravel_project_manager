<?php

namespace App\Livewire\Account\Customer;

use App\Models\Account;
use App\Models\Sale;
use App\Models\SaleItem;
use Livewire\Component;

class View extends Component
{
    protected $listeners = [
        'Customer-View-Component' => 'view',
    ];

    public $total_sales;

    public $sales;

    public $sale_items;

    public $item_count;

    public $accounts;

    public function view($account_id)
    {
        $this->mount($account_id);
        $this->dispatch('ToggleCustomerViewModal');
    }

    public function mount($account_id = null)
    {
        if ($account_id) {
            $account = Account::find($account_id);
            $this->accounts = $account->toArray();
            $this->total_sales = Sale::where('account_id', $account_id)->groupBy('account_id')
                ->selectRaw('sum(grand_total) as grand_total')
                ->selectRaw('sum(paid) as paid')
                ->selectRaw('sum(balance) as balance')
                ->first();
            $this->sales = Sale::where('account_id', $account_id)
                ->limit(20)
                ->latest()
                ->get(['id', 'date', 'invoice_no', 'grand_total', 'paid', 'balance']);
            $this->item_count = SaleItem::groupBy('product_id')
                ->whereHas('sale', function ($query) use ($account_id) {
                    return $query->where('sales.account_id', $account_id);
                })
                ->select('product_id')
                ->selectRaw('count(product_id) as count')
                ->get();
            $this->sale_items = SaleItem::limit(20)
                ->whereHas('sale', function ($query) use ($account_id) {
                    return $query->where('sales.account_id', $account_id);
                })
                ->latest()
                ->select('product_id', 'sale_id')
                ->get();
        }
    }

    public function render()
    {
        return view('livewire.account.customer.view');
    }
}
