<?php

namespace App\Livewire\Settings\AccountCategory;

use App\Actions\Settings\AccountCategory\CreateAction;
use App\Actions\Settings\AccountCategory\UpdateAction;
use App\Models\AccountCategory;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'AccountCategory-Page-Create-Component' => 'create',
        'AccountCategory-Page-Update-Component' => 'edit',
    ];

    public $accountCategories;

    public $parents;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleAccountCategoryModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleAccountCategoryModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            if (! app()->isProduction()) {
                $name = $faker->name;
            }
            $this->accountCategories = [
                'parent_id' => null,
                'name' => $name,
            ];
        } else {
            $accountCategory = AccountCategory::with('parent')->find($this->table_id);
            $this->accountCategories = $accountCategory->toArray();
        }
        $this->dispatch('SelectDropDownValues');
    }

    protected function rules()
    {
        return [
            'accountCategories.name' => ['required', 'unique:account_categories,name,'.$this->table_id],
        ];
    }

    protected $messages = [
        'accountCategories.name.required' => 'The name field is required',
        'accountCategories.name.unique' => 'The name is already Registered',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->accountCategories);
            } else {
                $response = (new UpdateAction())->execute($this->accountCategories, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->accountCategories['id'] = $response['data']['id'];
            $parent_id = $response['data']['parent_id'];
            $parent_name = $response['data']->parent?->name;
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleAccountCategoryModal');
            } else {
                $this->mount();
            }
            $this->accountCategories['parent_id'] = $parent_id;
            $this->accountCategories['parent']['name'] = $parent_name;
            $this->dispatch('RefreshAccountCategoryTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.account-category.page');
    }
}
