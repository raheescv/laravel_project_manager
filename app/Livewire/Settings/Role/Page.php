<?php

namespace App\Livewire\Settings\Role;

use App\Actions\Settings\Role\CreateAction;
use App\Actions\Settings\Role\UpdateAction;
use Faker\Factory;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Page extends Component
{
    protected $listeners = [
        'Role-Page-Create-Component' => 'create',
        'Role-Page-Update-Component' => 'edit',
    ];

    public $roles;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleRoleModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleRoleModal');
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
            $this->roles = [
                'name' => $name,
            ];
        } else {
            $role = Role::find($this->table_id);
            $this->roles = $role->toArray();
        }
    }
    public function save($close = false)
    {
        try {
            if (! $this->table_id) {
                $response = (new CreateAction)->execute($this->roles);
            } else {
                $response = (new UpdateAction)->execute($this->roles, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleRoleModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshRoleTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.role.page');
    }
}
