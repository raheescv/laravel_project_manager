<?php

namespace App\Livewire\Settings\Role;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Permissions extends Component
{
    public $role_id;

    public $role;

    public $select_all = '';

    public $permissions = [];

    public $module = [];

    public $assigned = [];

    public $selected = [];

    public $search = '';

    public function mount($role_id)
    {
        $this->role_id = $role_id;
        $this->role = Role::find($role_id);
        $this->assigned = $this->role->permissions()->pluck('id')->toArray();
        $this->selected = $this->role->permissions()->pluck('id')->toArray();
        $this->selected = array_flip($this->selected);
        $this->selected = array_map(function ($value) {
            return true;
        }, $this->selected);
    }

    public function moduleSelection()
    {
        $this->module = [];
        foreach ($this->permissions as $module => $actions) {
            $actionKeys = array_keys($actions);
            $allSelected = true;
            foreach ($actionKeys as $key) {
                if (! isset($this->selected[$key]) || $this->selected[$key] !== true) {
                    $allSelected = false;
                    break;
                }
            }

            $this->module[$module] = $allSelected;
        }
    }

    public function selectAll()
    {
        $modulePermission = Permission::pluck('id')->toArray();
        if (! $this->select_all) {
            $this->selected = [];
            $this->module = [];
        } else {
            foreach ($modulePermission as $permissionId) {
                $this->selected[$permissionId] = true;
            }
            $this->module = array_keys($this->permissions);
            $this->module = array_flip($this->module);
            $this->module = array_map(function ($value) {
                return true;
            }, $this->module);
        }
    }

    public function moduleSelect($module)
    {
        $modulePermission = Permission::where('name', 'LIKE', "%$module.%")->pluck('id')->toArray();
        if (! $this->module[$module]) {
            foreach ($modulePermission as $permissionId) {
                unset($this->selected[$permissionId]);
            }
            $this->select_all = false;
        } else {
            foreach ($modulePermission as $permissionId) {
                $this->selected[$permissionId] = true;
            }
        }
    }

    public function save()
    {
        try {
            if ($this->role['id'] == 1) {
                throw new \Exception('You cant edit Super Admin privileges', 1);
            }
            $this->role->syncPermissions([]);
            $this->selected = array_keys(array_filter($this->selected));
            $permissions = Permission::whereIn('id', $this->selected)->get();
            $this->role->syncPermissions($permissions);
            $this->dispatch('success', ['message' => 'roles Permission Updated Successfully']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
        $this->mount($this->role_id);
    }

    public function render()
    {
        $list = Permission::when($this->search ?? '', function ($query, $value) {
            $value = trim($value);
            $query->where('name', 'LIKE', '%'.$value.'%');
        })->pluck('name', 'id');
        $this->permissions = [];
        foreach ($list as $key => $name) {
            [$module, $action] = explode('.', $name);
            $this->permissions[$module][$key] = $action;
        }
        $this->moduleSelection();

        return view('livewire.settings.role.permissions');
    }
}
