<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ProductConfiguration extends Component
{
    public $barcode_type;

    public $barcode_prefix;

    public $sync_barcode_to_code;

    public $hide_out_of_stock_sale_items;

    public function mount()
    {
        $this->barcode_type = Configuration::where('key', 'barcode_type')->value('value');
        $this->barcode_prefix = Configuration::where('key', 'barcode_prefix')->value('value');
        $this->sync_barcode_to_code = Configuration::where('key', 'sync_barcode_to_code')->value('value') ?? 'no';
        $this->hide_out_of_stock_sale_items = Configuration::where('key', 'hide_out_of_stock_sale_items')->value('value') ?? 'yes';
    }

    public function save()
    {
        Configuration::updateOrCreate(['key' => 'barcode_type'], ['value' => $this->barcode_type]);
        Configuration::updateOrCreate(['key' => 'barcode_prefix'], ['value' => $this->barcode_prefix]);
        Configuration::updateOrCreate(['key' => 'sync_barcode_to_code'], ['value' => $this->sync_barcode_to_code]);
        Configuration::updateOrCreate(['key' => 'hide_out_of_stock_sale_items'], ['value' => $this->hide_out_of_stock_sale_items]);
        Cache::forget('barcode_prefix');
        $this->dispatch('success', ['message' => 'Updated Successfully']);
    }

    public function render()
    {
        return view('livewire.settings.product-configuration');
    }
}
