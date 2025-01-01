<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;

class View extends Component
{
    public $table_id;

    public $user;

    public function mount($table_id)
    {
        $this->table_id = $table_id;
        $this->user = User::find($this->table_id);
    }

    public function enabledWhatsapp()
    {
        $this->user->update(['is_whatsapp_enabled' => $this->user->is_whatsapp_enabled ? false : true]);
    }

    public function activeUser()
    {
        $this->user->update(['is_active' => $this->user->is_active ? false : true]);
    }

    public function render()
    {
        return view('livewire.user.view');
    }
}
