<?php

namespace App\Livewire\Property\Group;

use App\Actions\PropertyGroup\CreateAction;
use App\Actions\PropertyGroup\UpdateAction;
use App\Models\PropertyGroup;
use Faker\Factory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'PropertyGroup-Page-Create-Component' => 'create',
        'PropertyGroup-Page-Update-Component' => 'edit',
    ];

    public $formData;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('TogglePropertyGroupModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('TogglePropertyGroupModal');
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
                'arabic_name' => '',
                'lease_agreement_no' => '',
                'year' => '',
                'description' => '',
            ];
        } else {
            $item = PropertyGroup::find($this->table_id);
            $this->formData = $item->toArray();
        }
    }

    protected function rules()
    {
        return [
            'formData.name' => Rule::unique('property_groups', 'name')->where('tenant_id', session('tenant_id'))->ignore($this->table_id)->whereNull('deleted_at'),
        ];
    }

    protected $messages = [
        'formData.name.required' => 'The name field is required',
        'formData.name.unique' => 'The name is already registered',
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
                $this->dispatch('TogglePropertyGroupModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshPropertyGroupTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.property.group.page');
    }
}
