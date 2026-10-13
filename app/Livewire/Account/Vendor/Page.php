<?php

namespace App\Livewire\Account\Vendor;

use App\Actions\Account\Vendor\SaveAction;
use App\Enums\Account\VendorType;
use App\Models\Account;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Vendor-Page-Create-Component' => 'create',
        'Vendor-Page-Update-Component' => 'edit',
    ];

    public $accounts;

    public $parents;

    public $table_id;

    /** Personal details (ID, passport, nationality…) are folded away until asked for. */
    public bool $showMore = false;

    public function create($name = null, $mobile = null, $place = null)
    {
        $this->mount();
        if ($name) {
            $this->accounts['name'] = $name;
        }
        if ($mobile) {
            $this->accounts['mobile'] = $mobile;
        }
        if ($place) {
            $this->accounts['place'] = $place;
        }
        $this->dispatch('ToggleVendorModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleVendorModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            $account_type = 'liability';
            $mobile = '';
            $email = '';
            if (! app()->isProduction()) {
                $name = $faker->name;
                $mobile = rand(90000000, 99999999);
                $email = $faker->email;
            }
            $this->accounts = array_fill_keys(SaveAction::FIELDS, '') + [
                'account_type' => $account_type,
                'model' => 'Vendor',
            ];
            $this->accounts['name'] = $name;
            $this->accounts['mobile'] = $mobile;
            $this->accounts['email'] = $email;
            $this->showMore = false;
        } else {
            $account = Account::find($this->table_id);
            $this->accounts = collect(SaveAction::FIELDS)
                ->mapWithKeys(fn (string $field): array => [$field => $account->{$field} instanceof \DateTimeInterface ? $account->{$field}->format('Y-m-d') : ($account->{$field} ?? '')])
                ->all() + ['account_type' => $account->account_type, 'model' => 'Vendor'];
            $this->showMore = collect(['id_no', 'id_expiry_date', 'passport_no', 'occupation', 'nationality', 'company'])
                ->contains(fn (string $field): bool => filled($this->accounts[$field]));
        }
        $this->dispatch('SelectDropDownValues', $this->accounts);
    }

    public function updated($key, $value) {}

    protected function rules()
    {
        return [
            'accounts.name' => ['required', 'max:100'],
            'accounts.alias_name' => ['nullable', 'max:100'],
            'accounts.vendor_type' => ['nullable', \Illuminate\Validation\Rule::enum(VendorType::class)],
            'accounts.credit_period_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'accounts.cr_number' => ['nullable', 'max:50'],
            'accounts.tax_no' => ['nullable', 'max:50'],
            'accounts.mobile' => ['required', 'max:15'],
            'accounts.whatsapp_mobile' => ['nullable', 'max:15'],
            'accounts.emergency_contact_no' => ['nullable', 'max:20'],
            'accounts.email' => ['nullable', 'email', 'max:50'],
            'accounts.po_box' => ['nullable', 'max:30'],
            'accounts.contact_person' => ['nullable', 'max:100'],
            'accounts.place' => ['nullable', 'max:100'],
            'accounts.id_no' => ['nullable', 'max:50'],
            'accounts.id_expiry_date' => ['nullable', 'date'],
            'accounts.passport_no' => ['nullable', 'max:50'],
            'accounts.occupation' => ['nullable', 'max:100'],
            'accounts.nationality' => ['nullable', 'max:100'],
            'accounts.company' => ['nullable', 'max:150'],
        ];
    }

    protected $messages = [
        'accounts.name.required' => 'The name field is required',
        'accounts.name.max' => 'The name field must not be greater than 100 characters',
        'accounts.mobile.required' => 'The mobile field is required',
        'accounts.mobile.max' => 'The name field must not be greater than 15 characters',
        'accounts.email.max' => 'The name field must not be greater than 50 characters',
        'accounts.email.email' => 'The email field must be a valid email address.',
        'accounts.place.max' => 'The place must not be greater than 100 characters.',

    ];

    public function selectVendor($id)
    {
        $Vendor = Account::find($id);
        $this->dispatch('AddToVendorSelectBox', $Vendor);
        $this->dispatch('ToggleVendorModal');
    }

    public function save($close = false)
    {
        // TODO(C7): review save authz — inline quick-add via vendorSelect TomSelect (Vendor-Page-Create-Component) is embedded in many unrelated forms (purchase, vouchers, etc.); gating with vendor.create would break legitimate quick-create. Edit path (table_id) is reachable from the standalone vendor screen.
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new SaveAction())->execute($this->accounts);
            } else {
                $response = (new SaveAction())->execute($this->accounts, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $account_type = $response['data']['account_type'];
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleVendorModal');
            } else {
                $this->mount();
            }
            $this->accounts['account_type'] = $account_type;
            $this->dispatch('RefreshVendorTable');
            $this->dispatch('AddToVendorSelectBox', $response['data']);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.account.vendor.page');
    }
}
