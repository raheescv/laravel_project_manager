<?php

namespace App\Livewire\User;

use App\Actions\User\BranchAction;
use App\Models\Branch;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class View extends Component
{
    public $table_id;

    public $branches;

    public $branch_ids;

    public $default_branch_id;

    public $default_branch;

    public $role_names;

    public $user;

    public function mount($table_id)
    {
        $this->table_id = $table_id;
        $this->user = User::find($this->table_id);
        $this->role_names = $this->user->roles()->pluck('name')->toArray();
        $this->branches = Branch::pluck('name', 'id')->toArray();
        $this->branch_ids = $this->user->branches->pluck('branch_id', 'branch_id')->toArray();
        $this->default_branch[$this->default_branch_id] = $this->user?->branch?->name;
    }

    public function enabledWhatsapp()
    {
        $this->user->update(['is_whatsapp_enabled' => $this->user->is_whatsapp_enabled ? false : true]);
    }

    public function activeUser()
    {
        $this->user->update(['is_active' => $this->user->is_active ? false : true]);
    }

    public function saveRoles()
    {
        $this->user->syncRoles($this->role_names);
        $this->dispatch('success', ['message' => 'Successfully Updated Roles']);
    }

    public function saveBranches()
    {
        try {
            DB::beginTransaction();
            $action = new BranchAction();
            $response = $action->execute($this->table_id, $this->branch_ids);
            if (! $response['result']) {
                throw new Exception($response['message'], 1);
            }
            $this->user->update(['default_branch_id' => $this->default_branch_id]);
            DB::commit();
            $this->mount($this->table_id);
            $this->dispatch('success', ['title' => $response['message']]);
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $roles = Role::pluck('name', 'name')->toArray();

        return view('livewire.user.view', compact('roles'));
    }
}
