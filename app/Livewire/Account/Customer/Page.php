<?php

namespace App\Livewire\Account\Customer;

use App\Actions\Account\CreateAction;
use App\Actions\Account\UpdateAction;
use App\Models\Account;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Customer-Page-Create-Component' => 'create',
        'Customer-Page-Update-Component' => 'edit',
    ];

    public $existingCustomers = [];

    public $accounts;

    public $parents;

    public $table_id;

    public function create($name = null, $mobile = null)
    {
        $this->mount();
        if ($name) {
            $this->accounts['name'] = $name;
        }
        if ($mobile) {
            $this->accounts['mobile'] = $mobile;
        }
        $this->getCustomerByMobile();
        $this->dispatch('ToggleCustomerModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleCustomerModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            $account_type = 'asset';
            $mobile = '';
            $email = '';
            if (! app()->isProduction()) {
                $name = $faker->name;
                $mobile = rand(90000000, 99999999);
                $email = $faker->email;
            }
            $this->accounts = [
                'account_type' => $account_type,
                'name' => $name,
                'mobile' => $mobile,
                'whatsapp_mobile' => '',
                'email' => $email,

                'dob' => '',
                'id_no' => '',
                'nationality' => '',
                'company' => '',
            ];
        } else {
            $account = Account::find($this->table_id);
            $this->accounts = $account->toArray();
        }
        $this->dispatch('SelectDropDownValues', $this->accounts);
    }

    public function updated($key, $value)
    {
        if ($key == 'accounts.mobile') {
            $this->getCustomerByMobile();
        }
    }

    public function getCustomerByMobile()
    {
        $this->existingCustomers = Account::where('mobile', $this->accounts['mobile'])->get();
    }

    protected function rules()
    {
        return [
            'accounts.name' => ['required', 'max:100'],
            'accounts.email' => ['email', 'max:50'],
            'accounts.mobile' => ['required', 'max:15'],
        ];
    }

    protected $messages = [
        'accounts.name.required' => 'The name field is required',
        'accounts.name.max' => 'The name field must not be greater than 100 characters',
        'accounts.mobile.required' => 'The mobile field is required',
        'accounts.mobile.max' => 'The name field must not be greater than 15 characters',
        'accounts.email.max' => 'The name field must not be greater than 50 characters',
        'accounts.email.email' => 'The email field must be a valid email address.',
    ];

    public function selectCustomer($id)
    {
        $customer = Account::find($id);
        $this->dispatch('AddToCustomerSelectBox', $customer);
        $this->dispatch('ToggleCustomerModal');
    }

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->accounts);
            } else {
                $response = (new UpdateAction())->execute($this->accounts, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $account_type = $response['data']['account_type'];
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleCustomerModal');
            } else {
                $this->mount();
            }
            $this->accounts['account_type'] = $account_type;
            $this->dispatch('RefreshCustomerTable');
            $this->dispatch('AddToCustomerSelectBox', $response['data']);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.account.customer.page');
    }
}
