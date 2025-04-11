<?php

namespace App\Livewire\SaleReturn;

use App\Models\Inventory;
use App\Models\SaleItem;
use Livewire\Component;

class ProductList extends Component
{
    public $sale_id;

    public $product_key;

    public $products = [];

    protected $listeners = [
        'SaleReturn-getProducts-Component' => 'getProducts',
    ];

    public function mount()
    {
        $this->getProducts($this->sale_id, $this->product_key);
    }

    public function getProducts($sale_id = null, $product_key = null)
    {
        if ($sale_id) {
            $products = SaleItem::join('products', 'product_id', 'products.id')
                ->when($product_key, function ($query, $value) {
                    return $query->where('products.name', 'LIKE', '%'.$value.'%');
                })
                ->where('sale_items.sale_id', $sale_id)
                ->orderBy('products.name')
                ->select(
                    'sale_items.id as sale_item_id',
                    'sale_items.inventory_id',
                    'sale_items.product_id',
                    'sale_items.quantity',
                    'sale_items.unit_price',
                    'products.type',
                    'products.name',
                    'products.thumbnail',
                )
                ->get();
        } else {
            $products = Inventory::join('products', 'product_id', 'products.id')
                ->when($product_key, function ($query, $value) {
                    return $query->where('products.name', 'LIKE', '%'.$value.'%');
                })
                ->where('products.is_selling', true)
                ->orderBy('products.name')
                ->select(
                    'inventories.id as inventory_id',
                    'inventories.product_id',
                    'inventories.quantity',
                    'products.type',
                    'products.name',
                    'products.thumbnail',
                )
                ->selectRaw('NULL as sale_item_id')
                ->get();
        }
        $this->products = $products->map(function ($item) {
            return [
                'id' => $item->inventory_id,
                'sale_item_id' => $item->sale_item_id,
                'product_id' => $item->product_id,
                'type' => $item->type,
                'quantity' => $item->quantity,
                'name' => $item->name,
                'thumbnail' => $item->thumbnail,
                'mrp' => $item->unit_price,
            ];
        })->toArray();
    }

    public function selectItem($inventory_id, $sale_item_id)
    {
        $this->dispatch('SaleReturn-selectItem-Component', $inventory_id, $sale_item_id);
    }

    public function render()
    {
        return view('livewire.sale-return.product-list');
    }
}
