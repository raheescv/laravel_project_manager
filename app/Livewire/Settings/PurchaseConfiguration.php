<?php

namespace App\Livewire\Settings;

use App\Models\Branch;
use App\Models\Configuration;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class PurchaseConfiguration extends Component
{
    public $enable_barcode_print_after_submit;

    public $default_quantity;

    public $purchase_item_row_mode;

    public $default_purchase_branch_id;

    public $branches;

    public function mount()
    {
        $this->enable_barcode_print_after_submit = Configuration::where('key', 'enable_barcode_print_after_submit')->value('value') ?? 'no';
        $this->default_quantity = Configuration::where('key', 'purchase_default_quantity')->value('value') ?? '1';
        $this->purchase_item_row_mode = Configuration::where('key', 'purchase_item_row_mode')->value('value') ?? 'merge';
        $default_purchase_branch_id_value = Configuration::where('key', 'default_purchase_branch_id')->value('value');
        $this->default_purchase_branch_id = $default_purchase_branch_id_value ? json_decode($default_purchase_branch_id_value, true) : [1];
        $this->branches = Branch::orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function save()
    {
        Configuration::updateOrCreate(['key' => 'enable_barcode_print_after_submit'], ['value' => $this->enable_barcode_print_after_submit]);
        Configuration::updateOrCreate(['key' => 'purchase_default_quantity'], ['value' => $this->default_quantity]);
        Configuration::updateOrCreate(['key' => 'purchase_item_row_mode'], ['value' => $this->purchase_item_row_mode]);
        Configuration::updateOrCreate(['key' => 'default_purchase_branch_id'], ['value' => json_encode($this->default_purchase_branch_id)]);
        $this->dispatch('success', ['message' => 'Updated Successfully']);
        Artisan::call('optimize:clear');
    }

    public function render()
    {
        return view('livewire.settings.purchase-configuration');
    }
}
