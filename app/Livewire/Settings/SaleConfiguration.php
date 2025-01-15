<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
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

    public $sale_type;

    public function mount()
    {
        $this->default_status = Configuration::where('key', 'default_status')->value('value');
        $this->thermal_printer_style = Configuration::where('key', 'thermal_printer_style')->value('value');
        $this->thermal_printer_footer_english = Configuration::where('key', 'thermal_printer_footer_english')->value('value');
        $this->thermal_printer_footer_arabic = Configuration::where('key', 'thermal_printer_footer_arabic')->value('value');
        $this->enable_discount_in_print = Configuration::where('key', 'enable_discount_in_print')->value('value');
        $this->enable_total_quantity_in_print = Configuration::where('key', 'enable_total_quantity_in_print')->value('value');
        $this->enable_logo_in_print = Configuration::where('key', 'enable_logo_in_print')->value('value');
        $this->sale_type = Configuration::where('key', 'sale_type')->value('value');
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
        Configuration::updateOrCreate(['key' => 'sale_type'], ['value' => $this->sale_type]);
        $this->dispatch('success', ['message' => 'Updated Successfully']);
    }

    public function render()
    {
        return view('livewire.settings.sale-configuration');
    }
}
