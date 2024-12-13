<?php

namespace App\Livewire\ProductType;

use App\Actions\ProductType\CreateAction;
use App\Actions\ProductType\UpdateAction;
use App\Models\ProductType;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'ProductType-Page-Create-Component' => 'create',
        'ProductType-Page-Update-Component' => 'edit',
    ];

    public $product_types;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleProductTypeModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleProductTypeModal');
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
            $this->product_types = [
                'name' => $name,
            ];
        } else {
            $productType = ProductType::find($this->table_id);
            $this->product_types = $productType->toArray();
        }
    }

    protected function rules()
    {
        return [
            'product_types.name' => ['required', 'unique:product_types,name,' . $this->table_id],
        ];
    }

    protected $messages = [
        'product_types.name.required' => 'The name field is required',
        'product_types.name.unique' => 'The name is already Registered',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction)->execute($this->product_types);
            } else {
                $response = (new UpdateAction)->execute($this->product_types, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            if (! $close) {
                $this->dispatch('ToggleProductTypeModal');
            }
            $this->dispatch('RefreshProductTypeTable');
            $this->mount($this->table_id);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.product-type.page');
    }
}
