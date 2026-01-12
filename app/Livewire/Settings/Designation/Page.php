<?php

namespace App\Livewire\Settings\Designation;

use App\Actions\Settings\Designation\CreateAction;
use App\Actions\Settings\Designation\UpdateAction;
use App\Models\Designation;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Designation-Page-Create-Component' => 'create',
        'Designation-Page-Update-Component' => 'edit',
    ];

    public $designations;

    public $table_id;

    public function create($name = null)
    {
        $this->mount();
        $this->designations = [
            'name' => $name,
            'priority' => Designation::max('priority') + 1,
        ];
        $this->dispatch('ToggleDesignationModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleDesignationModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            if (! app()->isProduction()) {
                $name = $faker->jobTitle;
            }
            $this->designations = [
                'name' => $name,
                'priority' => 0,
            ];
        } else {
            $designation = Designation::find($this->table_id);
            $this->designations = $designation->toArray();
        }
    }

    protected function rules()
    {
        return [
            'designations.name' => ['required', 'max:100', \Illuminate\Validation\Rule::unique('designations', 'name')->where('tenant_id', Designation::getCurrentTenantId())->ignore($this->table_id)],
            'designations.priority' => ['nullable', 'integer'],
        ];
    }

    protected $messages = [
        'designations.name.required' => 'The name field is required',
        'designations.name.unique' => 'The name is already Registered',
        'designations.name.max' => 'The name field must not be greater than 100 characters.',
        'designations.priority.integer' => 'The priority field must be an integer.',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->designations);
            } else {
                $response = (new UpdateAction())->execute($this->designations, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleDesignationModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshDesignationTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.designation.page');
    }
}
