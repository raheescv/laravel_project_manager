<?php

namespace App\Livewire\Package;

use App\Actions\Package\CreateAction;
use App\Actions\Package\UpdateAction;
use App\Models\Package;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Package-Page-Create-Component' => 'create',
        'Package-Page-Update-Component' => 'edit',
    ];

    public $packages;

    public $parents;

    public $table_id;

    protected function validationAttributes()
    {
        return [
            'packages.name' => 'name',
            'packages.price' => 'price',
        ];
    }

    public function create()
    {
        $this->mount();
        $this->dispatch('TogglePackageModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('TogglePackageModal');
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

            $this->packages = [
                'name' => $name,
                'price' => '',
            ];

        } else {
            $package = Package::find($this->table_id);
            $this->packages = $package->toArray();
        }
    }

    protected function rules()
    {
        return [
            'packages.name' => ['required', 'string', 'max:255', 'unique:packages,name,'.($this->table_id)],
            'packages.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected $messages = [
        'packages.name.required' => 'The name field is required',
        'packages.name.unique' => 'The name is already Registered',
        'packages.price.required' => 'The price field is required',
        'packages.price.numeric' => 'The price must be a number',
        'packages.price.min' => 'The price must be at least 0',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->packages);
            } else {
                $response = (new UpdateAction())->execute($this->packages, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('TogglePackageModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshPackageTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.package.page');
    }
}

