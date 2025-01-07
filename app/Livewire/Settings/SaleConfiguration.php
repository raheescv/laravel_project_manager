<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Livewire\Component;

class SaleConfiguration extends Component
{
    public $default_status;

    public function mount()
    {
        $this->default_status = Configuration::where('key', 'default_status')->value('value');
    }

    public function save()
    {
        Configuration::updateOrCreate(['key' => 'default_status'], ['value' => $this->default_status]);
        $this->dispatch('success', ['message' => 'Updated Successfully']);
    }

    public function render()
    {
        return view('livewire.settings.sale-configuration');
    }
}
