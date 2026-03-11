<?php

namespace App\Livewire\Property\Property;

use App\Actions\Property\CreateAction;
use App\Actions\Property\UpdateAction;
use App\Models\Property;
use Livewire\Component;
use Livewire\WithFileUploads;

class Page extends Component
{
    use WithFileUploads;

    protected $listeners = [
        'Property-Page-Create-Component' => 'create',
        'Property-Page-Update-Component' => 'edit',
    ];

    public $formData;

    public $table_id;

    public $floor_plan;

    public function create()
    {
        $this->mount();
        $this->dispatch('TogglePropertyModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('TogglePropertyModal');
        $item = Property::with(['building', 'type'])->find($id);
        if ($item) {
            $this->dispatch('SelectPropertyDropDownValues', [
                'property_building_id' => $item->property_building_id,
                'building' => $item->building ? ['name' => $item->building->name] : null,
                'property_type_id' => $item->property_type_id,
                'type' => $item->type ? ['name' => $item->type->name] : null,
            ]);
        }
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        $this->floor_plan = null;
        if (! $this->table_id) {
            $this->formData = [
                'number' => '',
                'property_building_id' => '',
                'furniture' => 'Yes',
                'property_type_id' => '',
                'floor' => '',
                'hall' => '',
                'size' => '',
                'electricity' => '',
                'rent' => '',
                'rooms' => '',
                'ownership' => '',
                'kahramaa' => '',
                'parking' => '',
                'kitchen' => '',
                'toilet' => '',
                'flag' => 'active',
                'remark' => '',
                'floor_plan' => '',
            ];
        } else {
            $item = Property::find($this->table_id);
            $this->formData = $item->toArray();
        }
    }

    protected function rules()
    {
        return [
            'formData.number' => 'required|string|max:255',
            'formData.property_building_id' => 'required',
            'formData.property_type_id' => 'required',
            'formData.rent' => 'required|numeric|min:0',
            'formData.flag' => 'required|string',
            'floor_plan' => 'nullable|image|max:2048',
        ];
    }

    protected $messages = [
        'formData.number.required' => 'The number field is required',
        'formData.property_building_id.required' => 'The building field is required',
        'formData.property_type_id.required' => 'The type field is required',
        'formData.rent.required' => 'The rent field is required',
        'formData.flag.required' => 'The flag field is required',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            $data = $this->formData;

            if ($this->floor_plan) {
                $data['floor_plan'] = $this->floor_plan->store('property/floor-plans', 'public');
            }

            if (! $this->table_id) {
                $response = (new CreateAction())->execute($data);
            } else {
                $response = (new UpdateAction())->execute($data, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $message = $response['message'];
            $this->dispatch('success', ['message' => $message]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('TogglePropertyModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshPropertyTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.property.property.page');
    }
}
