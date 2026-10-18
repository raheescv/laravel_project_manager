<?php

namespace App\Livewire\Purchase;

use App\Models\Configuration;
use Livewire\Component;

class ColumnVisibility extends Component
{
    public $purchase_visible_column;

    public function mount()
    {
        $this->purchase_visible_column = self::visibleColumns();
    }

    /**
     * The saved column choices over the defaults, so a tenant that never saved any still gets every key.
     *
     * @return array<string, bool>
     */
    public static function visibleColumns(): array
    {
        $saved = json_decode((string) Configuration::where('key', 'purchase_visible_column')->value('value'), true);

        return array_merge(self::defaultColumns(), is_array($saved) ? $saved : []);
    }

    /**
     * @return array<string, bool>
     */
    protected static function defaultColumns(): array
    {
        return [
            'branch_id' => false,
            'vendor' => true,
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
        $this->purchase_visible_column[$column] = ! $this->purchase_visible_column[$column];
        Configuration::updateOrCreate(['key' => 'purchase_visible_column'], ['value' => json_encode($this->purchase_visible_column)]);
    }

    public function resetToDefaults()
    {
        $this->purchase_visible_column = self::defaultColumns();
        Configuration::updateOrCreate(['key' => 'purchase_visible_column'], ['value' => json_encode($this->purchase_visible_column)]);
    }

    public function render()
    {
        return view('livewire.purchase.column-visibility');
    }
}
