<?php

namespace App\Livewire\SaleReturn;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $sale_return_visible_column;

    public function mount()
    {
        $this->sale_return_visible_column = self::visibleColumns();
    }

    /**
     * The saved column choices over the defaults, so a tenant that never saved any still gets every key.
     *
     * @return array<string, bool>
     */
    public static function visibleColumns(): array
    {
        $saved = json_decode((string) Configuration::where('key', 'sale_return_visible_column')->value('value'), true);

        return array_merge(self::defaultColumns(), is_array($saved) ? $saved : []);
    }

    /**
     * @return array<string, bool>
     */
    protected static function defaultColumns(): array
    {
        return [
            'reference_no' => true,
            'branch_id' => false,
            'customer' => true,
            'gross_amount' => true,
            'item_discount' => false,
            'tax_amount' => false,
            'total' => false,
            'other_discount' => true,
            'grand_total' => true,
            'paid' => false,
            'balance' => false,
            'status' => false,
        ];
    }

    public function toggleColumn($column)
    {
        $this->sale_return_visible_column[$column] = ! $this->sale_return_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'sale_return_visible_column'], ['value' => json_encode($this->sale_return_visible_column)]);
    }

    public function resetToDefaults()
    {
        $this->sale_return_visible_column = self::defaultColumns();
        Configuration::updateOrCreate(['key' => 'sale_return_visible_column'], ['value' => json_encode($this->sale_return_visible_column)]);
    }

    public function render()
    {
        return view('livewire.sale-return.column-visibility');
    }
}
