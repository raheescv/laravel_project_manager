<?php

namespace App\Livewire\Settings\CustomerType;

use App\Actions\Settings\CustomerType\CreateAction;
use App\Actions\Settings\CustomerType\UpdateAction;
use App\Models\CustomerType;
use Faker\Factory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'CustomerType-Page-Create-Component' => 'create',
        'CustomerType-Page-Update-Component' => 'edit',
    ];

    public $customer_types;

    public $table_id;

    public function create($name = null)
    {
        $this->mount();
        if ($name) {
            $this->customer_types['name'] = $name;
        }
        $this->dispatch('ToggleCustomerTypeModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleCustomerTypeModal');
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
            $this->customer_types = [
                'name' => $name,
            ];
        } else {
            $customer_type = CustomerType::find($this->table_id);
            $this->customer_types = $customer_type->toArray();
        }
    }

    protected function rules()
    {
        return [
            'customer_types.name' => ['required', Rule::unique('customer_types', 'name')->ignore($this->table_id)],
            'customer_types.discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    protected $messages = [
        'customer_types.name.required' => 'The name field is required',
        'customer_types.name.unique' => 'The name is already Registered',
        'customer_types.discount_percentage.required' => 'The discount percentage is required',
        'customer_types.discount_percentage.numeric' => 'The discount percentage must be a number',
        'customer_types.discount_percentage.min' => 'The discount percentage must be at least 0',
        'customer_types.discount_percentage.max' => 'The discount percentage cannot exceed 100',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->customer_types);
            } else {
                $response = (new UpdateAction())->execute($this->customer_types, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleCustomerTypeModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshCustomerTypeTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.customer-type.page');
    }
}
