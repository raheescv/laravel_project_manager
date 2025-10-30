<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class SaleConfiguration extends Component
{
    public $default_status;

    public $thermal_printer_style;

    public $thermal_printer_footer_english;

    public $thermal_printer_footer_arabic;

    public $enable_discount_in_print;

    public $enable_total_quantity_in_print;

    public $enable_logo_in_print;

    public $enable_barcode_in_print;

    public $sale_type;

    public $default_customer_enabled;

    public $print_item_label; // 'product' or 'category'

    public $print_quantity_label; // 'quantity' or 'weight'

    public function mount()
    {
        $this->default_status = Configuration::where('key', 'default_status')->value('value');
        $this->thermal_printer_style = Configuration::where('key', 'thermal_printer_style')->value('value');
        $this->thermal_printer_footer_english = Configuration::where('key', 'thermal_printer_footer_english')->value('value');
        $this->thermal_printer_footer_arabic = Configuration::where('key', 'thermal_printer_footer_arabic')->value('value');
        $this->enable_discount_in_print = Configuration::where('key', 'enable_discount_in_print')->value('value');
        $this->enable_total_quantity_in_print = Configuration::where('key', 'enable_total_quantity_in_print')->value('value');
        $this->enable_logo_in_print = Configuration::where('key', 'enable_logo_in_print')->value('value');
        $this->enable_barcode_in_print = Configuration::where('key', 'enable_barcode_in_print')->value('value');
        $this->sale_type = Configuration::where('key', 'sale_type')->value('value');
        $this->default_customer_enabled = Configuration::where('key', 'default_customer_enabled')->value('value') ?? 'yes';
        $this->print_item_label = Configuration::where('key', 'print_item_label')->value('value') ?? 'product';
        $this->print_quantity_label = Configuration::where('key', 'print_quantity_label')->value('value') ?? 'quantity';
    }

    public function save()
    {
        Configuration::updateOrCreate(['key' => 'default_status'], ['value' => $this->default_status]);
        Configuration::updateOrCreate(['key' => 'thermal_printer_style'], ['value' => $this->thermal_printer_style]);
        Configuration::updateOrCreate(['key' => 'thermal_printer_footer_english'], ['value' => $this->thermal_printer_footer_english]);
        Configuration::updateOrCreate(['key' => 'thermal_printer_footer_arabic'], ['value' => $this->thermal_printer_footer_arabic]);
        Configuration::updateOrCreate(['key' => 'enable_discount_in_print'], ['value' => $this->enable_discount_in_print]);
        Configuration::updateOrCreate(['key' => 'enable_total_quantity_in_print'], ['value' => $this->enable_total_quantity_in_print]);
        Configuration::updateOrCreate(['key' => 'enable_logo_in_print'], ['value' => $this->enable_logo_in_print]);
        Configuration::updateOrCreate(['key' => 'enable_barcode_in_print'], ['value' => $this->enable_barcode_in_print]);
        Configuration::updateOrCreate(['key' => 'sale_type'], ['value' => $this->sale_type]);
        Configuration::updateOrCreate(['key' => 'default_customer_enabled'], ['value' => $this->default_customer_enabled]);
        Configuration::updateOrCreate(['key' => 'print_item_label'], ['value' => $this->print_item_label]);
        Configuration::updateOrCreate(['key' => 'print_quantity_label'], ['value' => $this->print_quantity_label]);
        $this->dispatch('success', ['message' => 'Updated Successfully']);
        Artisan::call('optimize:clear');
    }

    public function render()
    {
        return view('livewire.settings.sale-configuration');
    }
}
