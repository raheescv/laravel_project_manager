<?php

namespace App\Livewire\Report;

use App\Models\Configuration;
use Livewire\Component;

class PurchaseItemColumnVisibility extends Component
{
    public array $purchase_item_report_visible_column = [];

    public function mount(): void
    {
        $config = Configuration::where('key', 'purchase_item_report_visible_column')->value('value');
        $this->purchase_item_report_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    protected function getDefaultColumns(): array
    {
        return [
            'id' => true,
            'date' => true,
            'invoice_no' => true,
            'product_name' => true,
            'unit_price' => true,
            'quantity' => true,
            'base_unit_quantity' => false,
            'gross_amount' => true,
            'discount' => true,
            'net_amount' => true,
            'tax_amount' => true,
            'total' => true,
        ];
    }

    public function toggleColumn(string $column): void
    {
        if (! array_key_exists($column, $this->purchase_item_report_visible_column)) {
            return;
        }

        $this->purchase_item_report_visible_column[$column] = ! $this->purchase_item_report_visible_column[$column];
        Configuration::updateOrCreate( ['key' => 'purchase_item_report_visible_column'], ['value' => json_encode($this->purchase_item_report_visible_column)] );
    }

    public function resetToDefaults(): void
    {
        $this->purchase_item_report_visible_column = $this->getDefaultColumns();
        Configuration::updateOrCreate( ['key' => 'purchase_item_report_visible_column'], ['value' => json_encode($this->purchase_item_report_visible_column)] );
    }

    public function render()
    {
        return view('livewire.report.purchase-item-column-visibility');
    }
}
