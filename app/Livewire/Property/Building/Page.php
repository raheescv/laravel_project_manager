<?php

namespace App\Livewire\Property\Building;

use App\Actions\PropertyBuilding\CreateAction;
use App\Actions\PropertyBuilding\UpdateAction;
use App\Models\PropertyBuilding;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'PropertyBuilding-Page-Create-Component' => 'create',
        'PropertyBuilding-Page-Update-Component' => 'edit',
    ];

    public $formData;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('TogglePropertyBuildingModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('TogglePropertyBuildingModal');
        $item = PropertyBuilding::with('group')->find($id);
        if ($item) {
            $this->dispatch('SelectBuildingDropDownValues', [
                'property_group_id' => $item->property_group_id,
                'group' => $item->group ? ['name' => $item->group->name] : null,
            ]);
        }
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            if (! app()->isProduction()) {
                $name = $faker->company;
            }
            $this->formData = [
                'name' => $name,
                'property_group_id' => '',
                'arabic_name' => '',
                'created_date' => now()->subDay()->format('Y-m-d'),
                'reference_code' => '',
                'building_no' => '',
                'location' => '',
                'floors' => '',
                'investment' => '',
                'electricity' => '',
                'road' => '',
                'landmark' => '',
                'amount' => '',
                'ownership' => '',
                'status' => 'active',
                'remark' => '',
            ];
        } else {
            $item = PropertyBuilding::find($this->table_id);
            $this->formData = $item->toArray();
        }
    }

    protected function rules()
    {
        return [
            'formData.name' => 'required|string|max:30',
            'formData.property_group_id' => 'required',
        ];
    }

    protected $messages = [
        'formData.name.required' => 'The name field is required',
        'formData.property_group_id.required' => 'The group/project field is required',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->formData);
            } else {
                $response = (new UpdateAction())->execute($this->formData, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $message = $response['message'];
            $this->dispatch('success', ['message' => $message]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('TogglePropertyBuildingModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshPropertyBuildingTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.property.building.page');
    }
}
