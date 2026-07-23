<?php

namespace App\Livewire\Account\Customer;

use App\Models\Account;
use App\Models\Country;
use App\Traits\HasDocumentExpiryState;
use Livewire\Component;

class Kyc extends Component
{
    use HasDocumentExpiryState;

    public const KYC_FIELDS = [
        'emergency_contact_no',
        'po_box',
        'id_no',
        'id_expiry_date',
        'passport_no',
        'nationality',
        'dob',
        'marital_status',
        'occupation',
        'job',
        'sponsor_name',
        'company',
        'position_nature_of_business',
        'monthly_income',
        'residential_address',
        'employer_address',
        'contact_person',
        'contact_person_mobile',
        'cr_number',
        'cr_issue_date',
        'cr_expiry_date',
        'cp_number',
        'cp_issue_date',
        'cp_expiry_date',
        'eid_number',
        'eid_issue_date',
        'eid_expiry_date',
        'tax_card_no',
        'tax_card_issue_date',
    ];

    public $account_id;

    public $kyc = [];

    public $kyc_confirmed_at;

    public function mount($account_id = null)
    {
        $this->account_id = $account_id;
        $this->loadKyc();
    }

    public function loadKyc()
    {
        $this->kyc = [];
        $this->kyc_confirmed_at = null;
        if (! $this->account_id) {
            return;
        }
        $account = Account::find($this->account_id);
        if (! $account) {
            return;
        }
        foreach (self::KYC_FIELDS as $field) {
            $this->kyc[$field] = $account->{$field};
        }
        $this->kyc_confirmed_at = $account->kyc_confirmed_at;
    }

    public function saveKyc()
    {
        if (! auth()->user()->can('customer kyc.edit')) {
            $this->dispatch('error', ['message' => 'You do not have permission to edit customer KYC']);

            return;
        }
        $this->validate($this->kycRules());
        try {
            $account = Account::findOrFail($this->account_id);
            $data = collect($this->kyc)
                ->only(self::KYC_FIELDS)
                ->map(fn ($value) => $value === '' ? null : $value)
                ->toArray();
            $account->update($data);
            $this->loadKyc();
            $this->dispatch('success', ['message' => 'Customer KYC details saved']);
            $this->dispatch('RefreshCustomerView');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function resetKyc()
    {
        $this->resetValidation();
        $this->loadKyc();
    }

    /** Percentage of KYC fields that carry a value. */
    public function getCompletenessProperty(): array
    {
        $filled = collect(self::KYC_FIELDS)
            ->filter(fn ($field) => filled($this->kyc[$field] ?? null))
            ->count();
        $total = count(self::KYC_FIELDS);

        return [
            'filled' => $filled,
            'total' => $total,
            'missing' => $total - $filled,
            'percent' => $total > 0 ? (int) round(($filled / $total) * 100) : 0,
        ];
    }

    protected function kycRules()
    {
        return [
            'kyc.emergency_contact_no' => ['nullable', 'max:20'],
            'kyc.po_box' => ['nullable', 'max:20'],
            'kyc.id_no' => ['nullable', 'max:100'],
            'kyc.id_expiry_date' => ['nullable', 'date'],
            'kyc.passport_no' => ['nullable', 'max:30'],
            'kyc.nationality' => ['nullable', 'max:100'],
            'kyc.dob' => ['nullable', 'date'],
            'kyc.marital_status' => ['nullable', 'max:30'],
            'kyc.occupation' => ['nullable', 'max:100'],
            'kyc.job' => ['nullable', 'max:100'],
            'kyc.sponsor_name' => ['nullable', 'max:100'],
            'kyc.company' => ['nullable', 'max:100'],
            'kyc.position_nature_of_business' => ['nullable', 'max:150'],
            'kyc.monthly_income' => ['nullable', 'numeric', 'min:0'],
            'kyc.residential_address' => ['nullable', 'max:1000'],
            'kyc.employer_address' => ['nullable', 'max:1000'],
            'kyc.contact_person' => ['nullable', 'max:100'],
            'kyc.contact_person_mobile' => ['nullable', 'max:20'],
            'kyc.cr_number' => ['nullable', 'max:50'],
            'kyc.cr_issue_date' => ['nullable', 'date'],
            'kyc.cr_expiry_date' => ['nullable', 'date'],
            'kyc.cp_number' => ['nullable', 'max:50'],
            'kyc.cp_issue_date' => ['nullable', 'date'],
            'kyc.cp_expiry_date' => ['nullable', 'date'],
            'kyc.eid_number' => ['nullable', 'max:50'],
            'kyc.eid_issue_date' => ['nullable', 'date'],
            'kyc.eid_expiry_date' => ['nullable', 'date'],
            'kyc.tax_card_no' => ['nullable', 'max:50'],
            'kyc.tax_card_issue_date' => ['nullable', 'date'],
        ];
    }

    public function render()
    {
        $expiry = collect(['id_expiry_date', 'cr_expiry_date', 'cp_expiry_date', 'eid_expiry_date'])
            ->mapWithKeys(fn ($field) => [$field => $this->expiryState($this->kyc[$field] ?? null)]);

        return view('livewire.account.customer.kyc', [
            'readonly' => ! auth()->user()->can('customer kyc.edit'),
            'completeness' => $this->completeness,
            'expiry' => $expiry,
            // Kept out of the Livewire snapshot on purpose — as a public property the
            // ~250 country list was serialised into every request/response payload.
            'countries' => Country::orderBy('name')->pluck('name', 'name')->toArray(),
        ]);
    }
}
