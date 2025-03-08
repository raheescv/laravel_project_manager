<?php

namespace App\Livewire\Settings\Department;

use App\Actions\Settings\Department\CreateAction;
use App\Actions\Settings\Department\UpdateAction;
use App\Models\Department;
use Faker\Factory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Department-Page-Create-Component' => 'create',
        'Department-Page-Update-Component' => 'edit',
    ];

    public $departments;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleDepartmentModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleDepartmentModal');
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
            $this->departments = [
                'name' => $name,
            ];
        } else {
            $department = Department::find($this->table_id);
            $this->departments = $department->toArray();
        }
    }

    protected function rules()
    {
        return [
            'departments.name' => Rule::unique('departments', 'name')->ignore($this->table_id)->whereNull('deleted_at'),
        ];
    }

    protected $messages = [
        'departments.name.required' => 'The name field is required',
        'departments.name.unique' => 'The name is already Registered',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->departments);
            } else {
                $response = (new UpdateAction())->execute($this->departments, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleDepartmentModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshDepartmentTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.department.page');
    }
}
