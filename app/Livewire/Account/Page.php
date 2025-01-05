<?php

namespace App\Livewire\Account;

use App\Actions\Account\CreateAction;
use App\Actions\Account\UpdateAction;
use App\Models\Account;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Account-Page-Create-Component' => 'create',
        'Account-Page-Update-Component' => 'edit',
    ];

    public $accounts;

    public $parents;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleAccountModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleAccountModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            $account_type = '';
            if (! app()->isProduction()) {
                $name = $faker->name;
                $account_type = array_rand(accountTypes());
            }
            $this->accounts = [
                'account_type' => $account_type,
                'name' => $name,
            ];
        } else {
            $account = Account::find($this->table_id);
            $this->accounts = $account->toArray();
        }
        $this->dispatch('SelectDropDownValues', $this->accounts);
    }

    protected function rules()
    {
        return [
            'accounts.name' => ['required'],
            'accounts.account_type' => ['required'],
        ];
    }

    protected $messages = [
        'accounts.name.required' => 'The name field is required',
        'accounts.account_type.required' => 'The account type field is required',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction)->execute($this->accounts);
            } else {
                $response = (new UpdateAction)->execute($this->accounts, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $account_type = $response['data']['account_type'];
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleAccountModal');
            } else {
                $this->mount();
            }
            $this->accounts['account_type'] = $account_type;
            $this->dispatch('RefreshAccountTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.account.page');
    }
}
