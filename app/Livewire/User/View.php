<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class View extends Component
{
    public $table_id;

    public $role_names;

    public $user;

    public function mount($table_id)
    {
        $this->table_id = $table_id;
        $this->user = User::find($this->table_id);
        $this->role_names = $this->user->roles()->pluck('name')->toArray();
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

    public function render()
    {
        $roles = Role::pluck('name', 'name')->toArray();

        return view('livewire.user.view', compact('roles'));
    }
}
