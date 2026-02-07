<?php

namespace App\Livewire\Settings\Rack;

use App\Actions\Settings\Rack\CreateAction;
use App\Actions\Settings\Rack\UpdateAction;
use App\Models\Rack;
use Faker\Factory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Rack-Page-Create-Component' => 'create',
        'Rack-Page-Update-Component' => 'edit',
    ];

    public $racks;

    public $table_id;

    public function create($name = null)
    {
        $this->mount();
        $this->racks = [
            'name' => $name,
            'description' => '',
            'is_active' => true,
        ];
        $this->dispatch('ToggleRackModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleRackModal');
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
            $this->racks = [
                'name' => $name,
                'description' => '',
                'is_active' => true,
            ];
        } else {
            $rack = Rack::find($this->table_id);
            $this->racks = $rack->toArray();
        }
    }

    protected function rules()
    {
        return [
            'racks.name' => ['required', 'max:100', Rule::unique('racks', 'name')->where('tenant_id', Rack::getCurrentTenantId())->ignore($this->table_id)],
            'racks.description' => ['nullable', 'max:255'],
            'racks.is_active' => ['nullable', 'boolean'],
        ];
    }

    protected $messages = [
        'racks.name.required' => 'The name field is required',
        'racks.name.unique' => 'The name is already Registered',
        'racks.name.max' => 'The name field must not be greater than 100 characters.',
        'racks.description.max' => 'The description field must not be greater than 255 characters.',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            $data = $this->racks;
            $data['is_active'] = (bool) ($data['is_active'] ?? true);
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($data);
            } else {
                $response = (new UpdateAction())->execute($data, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleRackModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshRackTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.rack.page');
    }
}
