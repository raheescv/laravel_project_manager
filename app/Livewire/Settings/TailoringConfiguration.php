<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Livewire\Component;

class TailoringConfiguration extends Component
{
    public $redirection_page = 'create';

    public function mount()
    {
        $this->redirection_page = Configuration::where('key', 'tailoring_redirection_page')->value('value') ?? 'create';
    }

    public function save()
    {
        // TODO(C7): review save authz — writes app Configuration; tab gated by 'tailoring order.view' in settings/index.blade. Candidate: 'configuration.settings' or 'tailoring order.edit' (ambiguous, not gated to avoid deny-all).
        Configuration::updateOrCreate(['key' => 'tailoring_redirection_page'], ['value' => $this->redirection_page]);
        $this->dispatch('success', ['message' => 'Updated Successfully']);
    }

    public function render()
    {
        return view('livewire.settings.tailoring-configuration');
    }
}
