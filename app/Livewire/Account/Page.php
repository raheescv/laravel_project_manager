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

    public $accountCategories;
    public $parents;

    public $table_id;

    public $type_selection_freeze = false;

    public function create($name = null, $account_type = null)
    {
        $this->mount();
        $this->accounts['name'] = $name;
        $this->accounts['account_type'] = $account_type;
        if ($account_type) {
            $this->type_selection_freeze = true;
        }
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
        $this->type_selection_freeze = false;
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
            $account = Account::with('accountCategory:id,name')->find($this->table_id);
            $this->accounts = $account->toArray();
            if($account->account_category_id){
                $this->accountCategories[$account->accountCategory->id] = $account->accountCategory->name;
            }
        }
        $this->dispatch('SelectDropDownValues', $this->accounts);
    }

    protected function rules()
    {
        return [
            'accounts.name' => ['required', 'max:100'],
            'accounts.account_type' => ['required'],
        ];
    }

    protected $messages = [
        'accounts.name.required' => 'The name field is required',
        'accounts.name.max' => 'The name field must not be greater than 100 characters',
        'accounts.account_type.required' => 'The account type field is required',
    ];

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
            $this->dispatch('AddToAccountSelectBox', $response['data']);
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
