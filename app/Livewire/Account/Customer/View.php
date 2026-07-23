<?php

namespace App\Livewire\Account\Customer;

use App\Models\Account;
use App\Models\AccountNote;
use App\Models\RentOut;
use App\Models\Sale;
use App\Models\SaleReturn;
use Livewire\Component;

/**
 * Customer view shell — renders the premium hero, the headline KPIs and the tab
 * rail only. Every tab is its own Livewire component (BasicDetails, Kyc,
 * RentoutHistory, Sales, SaleReturns, SaleItems, SaleItemSummary) and is mounted
 * the first time its tab is opened, so a page load no longer runs every tab's
 * queries. Design system: resources/views/components/account/customer/premium.blade.php
 *
 * The hero data is loaded once (mount / RefreshCustomerView) and held in state —
 * render() itself hits no database, so switching tabs costs nothing.
 */
class View extends Component
{
    protected $listeners = [
        'Customer-View-Component' => 'view',
        'RefreshCustomerView' => 'reload',
    ];

    public $account_id;

    public $selected_tab = 'BasicDetails';

    /** Tabs opened at least once — keeps them mounted for instant re-visits. */
    public $loaded_tabs = ['BasicDetails' => true];

    public $accounts;

    public $kpi = [];

    public function view($account_id)
    {
        $this->mount($account_id);
        $this->dispatch('ToggleCustomerViewModal');
    }

    public function mount($account_id = null)
    {
        $this->account_id = $account_id;
        $this->selected_tab = 'BasicDetails';
        $this->loaded_tabs = ['BasicDetails' => true];
        $this->reload();
    }

    /** Re-read the header data (after an edit, or a KYC save). */
    public function reload()
    {
        $this->accounts = null;
        $this->kpi = [
            'billed' => 0, 'paid' => 0, 'balance' => 0, 'invoices' => 0, 'returned' => 0, 'returns' => 0,
            'agreements' => 0, 'rentals' => 0, 'agreement_sales' => 0, 'since' => null,
            'kyc_percent' => 0, 'kyc_missing' => count(Kyc::KYC_FIELDS), 'notes' => 0,
        ];

        if (! $this->account_id) {
            return;
        }

        $account = Account::with(['customerType', 'kycConfirmer:id,name'])->find($this->account_id);
        if (! $account) {
            return;
        }
        $this->accounts = $account->toArray();

        $sales = Sale::where('account_id', $account->id)
            ->selectRaw('COUNT(*) AS invoices, SUM(grand_total) AS grand_total, SUM(paid) AS paid, SUM(balance) AS balance, MIN(date) AS since')
            ->first();
        $returns = SaleReturn::where('account_id', $account->id)
            ->selectRaw('COUNT(*) AS returns_count, SUM(grand_total) AS grand_total')
            ->first();
        $agreements = RentOut::where('account_id', $account->id)
            ->selectRaw("COUNT(*) AS total, SUM(agreement_type = 'rental') AS rentals, SUM(agreement_type = 'lease') AS agreement_sales")
            ->first();

        $filled = collect(Kyc::KYC_FIELDS)->filter(fn ($field) => filled($account->{$field}))->count();

        $this->kpi = [
            'billed' => (float) ($sales->grand_total ?? 0),
            'paid' => (float) ($sales->paid ?? 0),
            'balance' => (float) ($sales->balance ?? 0),
            'invoices' => (int) ($sales->invoices ?? 0),
            'returned' => (float) ($returns->grand_total ?? 0),
            'returns' => (int) ($returns->returns_count ?? 0),
            'agreements' => (int) ($agreements->total ?? 0),
            'rentals' => (int) ($agreements->rentals ?? 0),
            'agreement_sales' => (int) ($agreements->agreement_sales ?? 0),
            'since' => $sales->since ?? null,
            'kyc_percent' => (int) round(($filled / count(Kyc::KYC_FIELDS)) * 100),
            'kyc_missing' => count(Kyc::KYC_FIELDS) - $filled,
            'notes' => AccountNote::where('account_id', $account->id)->count(),
        ];
    }

    public function selectTab($tab)
    {
        $this->selected_tab = $tab;
        $this->loaded_tabs[$tab] = true;
    }

    public function render()
    {
        return view('livewire.account.customer.view');
    }
}
