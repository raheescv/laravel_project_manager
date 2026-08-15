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

    /** Show only the abilities this role has NOT been granted yet. */
    public $only_not_granted = false;

    /** How many abilities in the current view are still not granted. */
    public $not_granted_count = 0;

    public function mount($role_id)
    {
        $this->role_id = $role_id;
        $this->role = Role::find($role_id);
        $assignedIds = $this->role->permissions()->pluck('id')->toArray();
        $this->assigned = $assignedIds;
        $this->selected = array_fill_keys($assignedIds, true);
    }

    public function updatedOnlyNotGranted()
    {
        $this->select_all = false;
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
            // Only drop what is on screen — anything hidden by the search or the
            // "Not granted yet" filter must keep its state or save() would revoke it.
            foreach ($visibleIds as $permissionId) {
                unset($this->selected[$permissionId]);
            }
            $this->module = [];
        } else {
            foreach ($visibleIds as $permissionId) {
                $this->selected[$permissionId] = true;
            }
            $this->module = array_fill_keys(array_keys($this->permissions), true);
        }
    }

    public function syncPermission()
    {
        // Re-seeds every ability in config/permissions.php. Same power as editing
        // the permission matrix, so it takes the same ability.
        abort_unless(auth()->user()?->can('role.permissions'), 403);
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
        // Visible ids only, so a filtered view never revokes an ability it is hiding.
        $modulePermission = array_keys($this->permissions[$module] ?? []);
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
        // Granting abilities to a role is privilege escalation if left open: it is
        // the one screen that can hand out every other permission in the system.
        abort_unless(auth()->user()?->can('role.permissions'), 403);
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

        $scoped = Permission::when($activeModule, function ($query) use ($activeModule) {
            [$allowedGroups, $allowedExact] = $this->resolveAllowedPermissions($activeModule);

            $query->where(function ($q) use ($allowedGroups, $allowedExact) {
                foreach ($allowedGroups as $group) {
                    $q->orWhere('name', 'LIKE', $group.'.%');
                }
                if (! empty($allowedExact)) {
                    $q->orWhereIn('name', $allowedExact);
                }
            });
        })->pluck('name', 'id');

        $list = $scoped;

        if ($this->search) {
            $needle = mb_strtolower(trim($this->search));
            $list = $list->filter(fn ($name) => str_contains(mb_strtolower($name), $needle));
        }

        // Counted before the toggle is applied, so the badge still reads "N left" once filtering is on.
        $this->not_granted_count = $list->keys()->diff($this->assigned)->count();

        if ($this->only_not_granted) {
            $list = $list->except($this->assigned);
        }

        $this->permissions = $this->group($list);
        $this->moduleSelection();

        // The summary badge and the "Selected Permissions" tab describe the whole role,
        // never just whatever the search or the not-granted filter happens to show.
        return view('livewire.settings.role.permissions', [
            'allPermissions' => $this->group($scoped),
        ]);
    }

    /** Turn an id => 'module.action' list into ['module' => [id => 'action']]. */
    private function group($list): array
    {
        $grouped = [];
        foreach ($list as $key => $name) {
            [$module, $action] = explode('.', $name, 2);
            $grouped[$module][$key] = $action;
        }

        return $grouped;
    }

    private function resolveAllowedPermissions(string $activeModule): array
    {
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

        return [$allowedGroups, $allowedExact];
    }
}
