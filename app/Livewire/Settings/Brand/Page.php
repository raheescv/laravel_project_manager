<?php

namespace App\Livewire\Settings\Brand;

use App\Actions\Settings\Brand\CreateAction;
use App\Actions\Settings\Brand\UpdateAction;
use App\Models\Brand;
use Faker\Factory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Brand-Page-Create-Component' => 'create',
        'Brand-Page-Update-Component' => 'edit',
    ];

    public $brands;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleBrandModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleBrandModal');
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
            $this->brands = [
                'name' => $name,
            ];
        } else {
            $brand = Brand::find($this->table_id);
            $this->brands = $brand->toArray();
        }
    }

    protected function rules()
    {
        return [
            'brands.name' => Rule::unique('brands', 'name')->ignore($this->table_id)->whereNull('deleted_at'),
        ];
    }

    protected $messages = [
        'brands.name.required' => 'The name field is required',
        'brands.name.unique' => 'The name is already Registered',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->brands);
            } else {
                $response = (new UpdateAction())->execute($this->brands, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleBrandModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshBrandTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.brand.page');
    }
}
