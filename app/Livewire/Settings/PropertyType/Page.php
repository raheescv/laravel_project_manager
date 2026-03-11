<?php

namespace App\Livewire\Settings\PropertyType;

use App\Actions\Settings\PropertyType\CreateAction;
use App\Actions\Settings\PropertyType\UpdateAction;
use App\Models\PropertyType;
use Faker\Factory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'PropertyType-Page-Create-Component' => 'create',
        'PropertyType-Page-Update-Component' => 'edit',
    ];

    public $formData;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('TogglePropertyTypeModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('TogglePropertyTypeModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            if (! app()->isProduction()) {
                $name = $faker->word;
            }
            $this->formData = [
                'name' => $name,
                'description' => '',
            ];
        } else {
            $item = PropertyType::find($this->table_id);
            $this->formData = $item->toArray();
        }
    }

    protected function rules()
    {
        return [
            'formData.name' => Rule::unique('property_types', 'name')->where('tenant_id', session('tenant_id'))->ignore($this->table_id)->whereNull('deleted_at'),
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
                $this->dispatch('TogglePropertyTypeModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshPropertyTypeTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.property-type.page');
    }
}
