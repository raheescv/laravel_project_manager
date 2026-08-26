<?php

namespace App\Livewire\Sale;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $sale_visible_column;

    public $paymentMethods = [];

    public function mount()
    {
        // Merged over the defaults rather than used raw: a tenant who saved their
        // column choices before a new column existed would otherwise never be
        // offered it, because this list is what the toggle panel renders.
        $config = Configuration::where('key', 'sale_visible_column')->value('value');
        $this->sale_visible_column = array_merge(self::defaultColumns(), json_decode((string) $config, true) ?: []);
    }

    /**
     * @return array<string, bool>
     */
    public static function defaultColumns(): array
    {
        return [
            'created_at' => false,
            'reference_no' => false,
            'source' => false,
            'branch_id' => false,
            'created_by' => true,
            'customer' => true,
            'payment_method_name' => true,
            'gross_amount' => false,
            'item_discount' => false,
            'tax_amount' => false,
            'total' => false,
            'other_discount' => false,
            'freight' => false,
            'grand_total' => true,
            'paid' => true,
            'balance' => true,
            'status' => false,
        ];
    }

    public function toggleColumn($column)
    {
        $this->sale_visible_column[$column] = ! $this->sale_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'sale_visible_column'], ['value' => json_encode($this->sale_visible_column)]);
    }

    public function resetToDefaults()
    {
        $this->sale_visible_column = self::defaultColumns();
        Configuration::updateOrCreate(['key' => 'sale_visible_column'], ['value' => json_encode($this->sale_visible_column)]);
    }

    public function render()
    {
        return view('livewire.sale.column-visibility');
    }
}
