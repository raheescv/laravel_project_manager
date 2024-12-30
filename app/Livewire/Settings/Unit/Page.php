<?php

namespace App\Livewire\Settings\Unit;

use App\Actions\Settings\Unit\CreateAction;
use App\Actions\Settings\Unit\UpdateAction;
use App\Models\Unit;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Unit-Page-Create-Component' => 'create',
        'Unit-Page-Update-Component' => 'edit',
    ];

    public $units;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleUnitModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleUnitModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            $code = '';
            if (! app()->isProduction()) {
                $name = $faker->text(10);
                $code = $faker->hexcolor;
            }
            $this->units = [
                'code' => $code,
                'name' => $name,
            ];
        } else {
            $unit = Unit::find($this->table_id);
            $this->units = $unit->toArray();
        }
    }

    protected function rules()
    {
        return [
            'units.name' => ['required', 'max:10', 'unique:units,name,' . $this->table_id],
            'units.code' => ['required', 'max:10', 'unique:units,code,' . $this->table_id],
        ];
    }

    protected $messages = [
        'units.name.required' => 'The name field is required',
        'units.name.unique' => 'The name is already Registered',
        'units.name.max' => 'The name field must not be greater than 10 characters.',
        'units.code.required' => 'The code field is required',
        'units.code.unique' => 'The code is already Registered',
        'units.code.max' => 'The code field must not be greater than 10 characters.',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction)->execute($this->units);
            } else {
                $response = (new UpdateAction)->execute($this->units, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->units['id'] = $response['data']['id'];
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleUnitModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshUnitTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.unit.page');
    }
}
