<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ModuleConfiguration extends Component
{
    public string $active_module = '';

    /** The system currently persisted, so the page can preview what a switch changes. */
    #[Locked]
    public string $saved_module = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->is_super_admin, 403);

        $this->active_module = Configuration::where('key', 'active_module')->value('value') ?? '';
        $this->saved_module = $this->active_module;
    }

    public function save(): void
    {
        abort_unless(Auth::user()->is_super_admin, 403);

        $this->validate([
            'active_module' => ['required', 'string', 'in:'.implode(',', array_keys(config('modules.systems', [])))],
        ]);

        Configuration::updateOrCreate(['key' => 'active_module'], ['value' => $this->active_module]);
        $this->saved_module = $this->active_module;

        $this->dispatch('success', ['message' => 'Module configuration saved. Role permissions will now be filtered accordingly.']);
    }

    public function render(): View
    {
        $systems = config('modules.systems', []);
        $selected = $systems[$this->active_module] ?? [];
        $saved = $systems[$this->saved_module] ?? [];
        $isChanged = $this->saved_module !== '' && $this->active_module !== $this->saved_module;

        return view('livewire.settings.module-configuration', [
            'systems' => $systems,
            'moduleLabels' => collect(config('modules.modules', []))->map(fn (array $module): ?string => $module['label'] ?? null)->filter()->all(),
            'ledes' => collect(config('modules.login', []))->map(fn (array $copy): ?string => $copy['lede'] ?? null)->filter()->all(),
            'selectedModules' => $selected,
            'addedModules' => $isChanged ? array_values(array_diff($selected, $saved)) : [],
            'removedModules' => $isChanged ? array_values(array_diff($saved, $selected)) : [],
            'isChanged' => $isChanged,
        ]);
    }
}
