<?php

namespace App\Livewire\Product;

use App\Actions\Product\ProductUnit\CreateAction;
use App\Actions\Product\ProductUnit\UpdateAction;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Models\UniversalUnitConversion;
use Livewire\Component;

class Units extends Component
{
    protected $listeners = [
        'Product-Units-Create-Component' => 'create',
        'Product-Units-Update-Component' => 'edit',
    ];

    public $units;

    public $product_units;

    public $product_id;

    public $table_id;

    /** @var array sub_unit_id => conversion_factor from Universal UOM for this product's base unit */
    public $universalConversionFactors = [];

    public function create()
    {
        $this->mount($this->product_id);
        $this->dispatch('ToggleProductUnitModal');
    }

    public function edit($id)
    {
        $this->mount($this->product_id, $id);
        $this->dispatch('ToggleProductUnitModal');
    }

    public function mount($product_id, $table_id = null)
    {
        $this->product_id = $product_id;
        $product = Product::with('unit')->find($this->product_id);
        if ($product) {
            $this->table_id = $table_id;
            $this->units = Unit::pluck('name', 'id')->toArray();
            $this->universalConversionFactors = $this->loadUniversalConversionFactors($product->unit_id);
            if (! $this->table_id) {
                $this->product_units = [
                    'product_id' => $product->id,
                    'product' => ['unit' => ['name' => $product->unit->name ?? '']],
                    'sub_unit_id' => '',
                    'conversion_factor' => '',
                    'barcode' => '',
                ];
            } else {
                $product_units = ProductUnit::with('product:id,unit_id', 'product.unit:id,name')->find($this->table_id);
                $this->product_units = $product_units->toArray();
            }
        }
    }

    /**
     * Load Universal UOM conversion factors for the given base unit (sub_unit_id => conversion_factor).
     */
    protected function loadUniversalConversionFactors(?int $baseUnitId): array
    {
        if (! $baseUnitId) {
            return [];
        }

        return UniversalUnitConversion::where('base_unit_id', $baseUnitId)
            ->get()
            ->pluck('conversion_factor', 'sub_unit_id')
            ->toArray();
    }

    /**
     * When sub unit is selected, apply conversion factor from Universal UOM if available.
     */
    public function applyUniversalConversion(int $subUnitId): void
    {
        if (isset($this->universalConversionFactors[$subUnitId])) {
            $this->product_units['conversion_factor'] = $this->universalConversionFactors[$subUnitId];
        }
    }

    public function updatedProductUnitsSubUnitId($value): void
    {
        $value = is_numeric($value) ? (int) $value : null;
        if ($value && isset($this->universalConversionFactors[$value])) {
            $this->product_units['conversion_factor'] = $this->universalConversionFactors[$value];
        }
    }

    protected function rules()
    {
        return [
            'product_units.sub_unit_id' => ['required'],
            'product_units.conversion_factor' => ['required'],
        ];
    }

    protected $messages = [
        'product_units.sub_unit_id.required' => 'The sub unit field is required',
        'product_units.conversion_factor.required' => 'The conversion factor field is required',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->product_units);
            } else {
                $response = (new UpdateAction())->execute($this->product_units, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            if (! $close) {
                $this->dispatch('ToggleProductUnitModal');
            } else {
                $this->mount($this->product_id, $this->table_id);
            }
            $this->dispatch('Product-Refresh-Component');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.product.units');
    }
}
