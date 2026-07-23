<?php

namespace App\Livewire\Account\Customer;

use App\Models\Account;
use App\Models\Sale;
use App\Traits\HasDocumentExpiryState;
use Livewire\Component;

class BasicDetails extends Component
{
    use HasDocumentExpiryState;

    public $account_id;

    public function mount($account_id = null)
    {
        $this->account_id = $account_id;
    }

    public function confirmBasicDetails()
    {
        if (! auth()->user()->can('customer kyc.confirm')) {
            $this->dispatch('error', ['message' => 'You do not have permission to confirm customer details']);

            return;
        }
        try {
            $account = Account::findOrFail($this->account_id);
            $account->update([
                'kyc_confirmed_at' => now(),
                'kyc_confirmed_by' => auth()->id(),
            ]);
            $this->dispatch('success', ['message' => 'Customer basic details confirmed']);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $account = $this->account_id
            ? Account::with(['customerType', 'kycConfirmer:id,name'])->find($this->account_id)
            : null;

        $snapshot = ['count' => 0, 'average' => 0, 'last_date' => null];
        if ($account) {
            $stats = Sale::where('account_id', $account->id)
                ->selectRaw('COUNT(*) AS invoices, AVG(grand_total) AS average, MAX(date) AS last_date')
                ->first();
            $snapshot = [
                'count' => (int) ($stats->invoices ?? 0),
                'average' => (float) ($stats->average ?? 0),
                'last_date' => $stats->last_date ?? null,
            ];
        }

        $documents = collect([
            'National ID' => $account?->id_expiry_date,
            'CR' => $account?->cr_expiry_date,
            'CP' => $account?->cp_expiry_date,
            'EID' => $account?->eid_expiry_date,
        ])->map(fn ($date, $label) => $this->expiryState($date));

        return view('livewire.account.customer.basic-details', [
            'account' => $account,
            'snapshot' => $snapshot,
            'documents' => $documents,
        ]);
    }
}
