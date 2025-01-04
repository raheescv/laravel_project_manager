<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Livewire\Component;

class Configurations extends Component
{
    public $barcode_type;

    public function mount()
    {
        $this->barcode_type = Configuration::firstWhere('key', 'barcode_type')?->value('value');
    }

    public function save()
    {
        Configuration::updateOrCreate(['key' => 'barcode_type'], ['value' => $this->barcode_type]);
    }

    public function render()
    {
        return view('livewire.settings.configurations');
    }
}
