<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ModuleConfiguration extends Component
{
    public string $active_module = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->is_super_admin, 403);

        $this->active_module = Configuration::where('key', 'active_module')->value('value') ?? '';
    }

    public function save(): void
    {
        abort_unless(Auth::user()->is_super_admin, 403);

        $this->validate([
            'active_module' => ['required', 'string', 'in:'.implode(',', array_keys(config('modules.systems', [])))],
        ]);

        Configuration::updateOrCreate( ['key' => 'active_module'], ['value' => $this->active_module] );

        $this->dispatch('success', ['message' => 'Module configuration saved. Role permissions will now be filtered accordingly.']);
    }

    public function render()
    {
        return view('livewire.settings.module-configuration', [
            'systems' => config('modules.systems', []),
        ]);
    }
}
