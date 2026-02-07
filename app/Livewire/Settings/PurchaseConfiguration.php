<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class PurchaseConfiguration extends Component
{
    public $enable_barcode_print_after_submit;

    public $default_quantity;

    public function mount()
    {
        $this->enable_barcode_print_after_submit = Configuration::where('key', 'enable_barcode_print_after_submit')->value('value') ?? 'no';
        $this->default_quantity = Configuration::where('key', 'purchase_default_quantity')->value('value') ?? '1';
    }

    public function save()
    {
        Configuration::updateOrCreate(['key' => 'enable_barcode_print_after_submit'], ['value' => $this->enable_barcode_print_after_submit]);
        Configuration::updateOrCreate(['key' => 'purchase_default_quantity'], ['value' => $this->default_quantity]);
        $this->dispatch('success', ['message' => 'Updated Successfully']);
        Artisan::call('optimize:clear');
    }

    public function render()
    {
        return view('livewire.settings.purchase-configuration');
    }
}
