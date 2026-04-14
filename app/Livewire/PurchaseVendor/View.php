<?php

namespace App\Livewire\PurchaseVendor;

use App\Models\Account;
use App\Models\Grn;
use App\Models\LocalPurchaseOrder;
use App\Models\Models\Views\Ledger;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class View extends Component
{
    public $vendor_id;

    public $vendor;

    public $selected_tab = 'Statement';

    public $statement_from_date;

    public $statement_to_date;

    public $statement_limit = 20;

    public $purchase_from_date;

    public $purchase_to_date;

    public $purchase_limit = 20;

    public $lpo_limit = 20;

    public $grn_limit = 20;

    public $lpo_purchase_limit = 20;

    public function mount($vendor_id)
    {
        $this->vendor_id = $vendor_id;
        $this->statement_from_date = date('Y-m-d', strtotime('-3 months'));
        $this->statement_to_date = date('Y-m-d');
        $this->purchase_from_date = date('Y-m-d', strtotime('-3 months'));
        $this->purchase_to_date = date('Y-m-d');
    }

    public function render()
    {
        $this->vendor = Account::vendor()->find($this->vendor_id)?->toArray();

        $total_purchases = DB::table('purchases')
            ->where('account_id', $this->vendor_id)
            ->whereNull('deleted_at')
            ->selectRaw('SUM(grand_total) AS grand_total, SUM(paid) AS paid, SUM(balance) AS balance')
            ->first();

        $statements = Ledger::where('account_id', $this->vendor_id)
            ->when($this->statement_from_date, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($this->statement_to_date, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->limit($this->statement_limit)
            ->get();

        $purchases = Purchase::where('account_id', $this->vendor_id)
            ->when($this->purchase_from_date, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($this->purchase_to_date, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->latest()
            ->limit($this->purchase_limit)
            ->get(['id', 'date', 'invoice_no', 'total', 'other_discount', 'grand_total', 'paid', 'balance', 'status']);

        $lpos = LocalPurchaseOrder::where('vendor_id', $this->vendor_id)
            ->with('creator')
            ->latest()
            ->limit($this->lpo_limit)
            ->get();

        $grns = Grn::where('vendor_id', $this->vendor_id)
            ->with('creator', 'localPurchaseOrder')
            ->latest()
            ->limit($this->grn_limit)
            ->get();

        $lpo_purchases = Purchase::where('account_id', $this->vendor_id)
            ->whereNotNull('local_purchase_order_id')
            ->with('localPurchaseOrder')
            ->latest()
            ->limit($this->lpo_purchase_limit)
            ->get(['id', 'date', 'invoice_no', 'local_purchase_order_id', 'total', 'other_discount', 'grand_total', 'paid', 'balance', 'status']);

        return view('livewire.purchase-vendor.view', [
            'total_purchases' => $total_purchases,
            'statements' => $statements,
            'purchases' => $purchases,
            'lpos' => $lpos,
            'grns' => $grns,
            'lpo_purchases' => $lpo_purchases,
        ]);
    }
}
