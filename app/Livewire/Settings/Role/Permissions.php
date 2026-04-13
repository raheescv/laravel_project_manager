<?php

namespace App\Livewire\Settings\Role;

use App\Models\Configuration;
use Database\Seeders\PermissionSeeder;
use Exception;
use Illuminate\Support\Facades\Auth;
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
        // Use only the IDs currently visible — already filtered by active_module in render()
        $visibleIds = collect($this->permissions)->flatMap(fn ($actions) => array_keys($actions))->all();

        if (! $this->select_all) {
            $this->selected = [];
            $this->module = [];
        } else {
            foreach ($visibleIds as $permissionId) {
                $this->selected[$permissionId] = true;
            }
            $this->module = array_keys($this->permissions);
            $this->module = array_flip($this->module);
            $this->module = array_map(fn () => true, $this->module);
        }
    }

    public function syncPermission()
    {
        try {
            $seeder = new PermissionSeeder();
            $seeder->run();

            // Re-mount to refresh permissions
            $this->mount($this->role_id);

            $this->dispatch('success', ['message' => 'Permissions synchronized successfully']);
        } catch (Exception $e) {
            $this->dispatch('error', ['message' => 'Error synchronizing permissions: '.$e->getMessage()]);
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
            if ($this->role['id'] == 1 && Auth::user()->id != 3) {
                throw new Exception('You cant edit Super Admin privileges', 1);
            }
            $this->role->syncPermissions([]);
            $this->selected = array_keys(array_filter($this->selected));
            $permissions = Permission::whereIn('id', $this->selected)->get();
            $this->role->syncPermissions($permissions);
            $this->dispatch('success', ['message' => 'roles Permission Updated Successfully']);
        } catch (Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
        $this->mount($this->role_id);
    }

    public function render()
    {
        $activeModule = Configuration::where('key', 'active_module')->value('value');

        $list = Permission::when($this->search ?? '', function ($query, $value) {
            $value = trim($value);

            return $query->where('name', 'LIKE', '%'.$value.'%');
        })->when($activeModule, function ($query) use ($activeModule) {
            $systemModules = config("modules.systems.{$activeModule}", []);
            $moduleDefs = config('modules.modules', []);

            $allowedGroups = [];
            $allowedExact = [];

            foreach ($systemModules as $moduleKey) {
                foreach ($moduleDefs[$moduleKey]['permissions'] ?? [] as $perm) {
                    if (str_contains($perm, '.')) {
                        $allowedExact[] = $perm;
                    } else {
                        $allowedGroups[] = $perm;
                    }
                }
            }

            $query->where(function ($q) use ($allowedGroups, $allowedExact) {
                foreach ($allowedGroups as $group) {
                    $q->orWhere('name', 'LIKE', $group.'.%');
                }
                if (! empty($allowedExact)) {
                    $q->orWhereIn('name', $allowedExact);
                }
            });
        })->pluck('name', 'id');

        $this->permissions = [];
        foreach ($list as $key => $name) {
            [$module, $action] = explode('.', $name, 2);
            $this->permissions[$module][$key] = $action;
        }
        $this->moduleSelection();

        return view('livewire.settings.role.permissions');
    }
}
