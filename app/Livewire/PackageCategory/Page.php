<?php

namespace App\Livewire\PackageCategory;

use App\Actions\PackageCategory\CreateAction;
use App\Actions\PackageCategory\UpdateAction;
use App\Models\PackageCategory;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'PackageCategory-Page-Create-Component' => 'create',
        'PackageCategory-Page-Update-Component' => 'edit',
    ];

    public $package_categories;

    public $parents;

    public $table_id;

    protected function validationAttributes()
    {
        return [
            'package_categories.name' => 'name',
            'package_categories.price' => 'price',
        ];
    }

    public function create()
    {
        $this->mount();
        $this->dispatch('TogglePackageCategoryModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('TogglePackageCategoryModal');
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

            $this->package_categories = [
                'name' => $name,
                'price' => '',
            ];

        } else {
            $packageCategory = PackageCategory::find($this->table_id);
            $this->package_categories = $packageCategory->toArray();
        }
    }

    protected function rules()
    {
        return [
            'package_categories.name' => ['required', 'string', 'max:255', 'unique:package_categories,name,'.($this->table_id)],
            'package_categories.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected $messages = [
        'package_categories.name.required' => 'The name field is required',
        'package_categories.name.unique' => 'The name is already Registered',
        'package_categories.price.required' => 'The price field is required',
        'package_categories.price.numeric' => 'The price must be a number',
        'package_categories.price.min' => 'The price must be at least 0',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->package_categories);
            } else {
                $response = (new UpdateAction())->execute($this->package_categories, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('TogglePackageCategoryModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshPackageCategoryTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.package-category.page');
    }
}
