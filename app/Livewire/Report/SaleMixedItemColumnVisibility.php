<?php

namespace App\Livewire\Report;

use App\Models\Configuration;
use Livewire\Component;

class SaleMixedItemColumnVisibility extends Component
{
    public array $sale_mixed_item_report_visible_column = [];

    public function mount(): void
    {
        $config = Configuration::where('key', 'sale_mixed_item_report_visible_column')->value('value');
        $this->sale_mixed_item_report_visible_column = $config ? json_decode($config, true) : $this->getDefaultColumns();
    }

    protected function getDefaultColumns(): array
    {
        return [
            'created_at' => true,
            'type' => true,
            'date' => true,
            'reference' => true,
            'product_name' => true,
            'product_code' => true,
            'unit_price' => true,
            'quantity' => true,
            'gross_amount' => true,
            'discount' => true,
            'net_amount' => true,
            'tax_amount' => true,
            'total' => true,
        ];
    }

    public function toggleColumn(string $column): void
    {
        if (! array_key_exists($column, $this->sale_mixed_item_report_visible_column)) {
            return;
        }

        $this->sale_mixed_item_report_visible_column[$column] = ! $this->sale_mixed_item_report_visible_column[$column];
        Configuration::updateOrCreate(
            ['key' => 'sale_mixed_item_report_visible_column'],
            ['value' => json_encode($this->sale_mixed_item_report_visible_column)]
        );
    }

    public function render()
    {
        return view('livewire.report.sale-mixed-item-column-visibility');
    }
}
