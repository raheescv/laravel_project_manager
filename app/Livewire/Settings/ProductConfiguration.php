<?php

namespace App\Livewire\Settings;

use App\Actions\Settings\UpdateProductConfigurationAction;
use Livewire\Component;

class ProductConfiguration extends Component
{
    public $barcode_type;

    public $barcode_prefix;

    public $sync_barcode_to_code;

    public $hide_out_of_stock_sale_items;

    public function mount()
    {
        foreach (UpdateProductConfigurationAction::current() as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function save()
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);
        (new UpdateProductConfigurationAction())->execute([
            'barcode_type' => $this->barcode_type ?? '',
            'barcode_prefix' => $this->barcode_prefix ?? '',
            'sync_barcode_to_code' => $this->sync_barcode_to_code ?? 'no',
            'hide_out_of_stock_sale_items' => $this->hide_out_of_stock_sale_items ?? 'no',
        ], auth()->user());
        $this->dispatch('success', ['message' => 'Updated Successfully']);
    }

    public function render()
    {
        return view('livewire.settings.product-configuration');
    }
}
