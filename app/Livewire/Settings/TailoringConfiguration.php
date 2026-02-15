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
        Configuration::updateOrCreate(['key' => 'tailoring_redirection_page'], ['value' => $this->redirection_page]);
        $this->dispatch('success', ['message' => 'Updated Successfully']);
    }

    public function render()
    {
        return view('livewire.settings.tailoring-configuration');
    }
}
