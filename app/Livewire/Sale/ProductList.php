<?php

namespace App\Livewire\Sale;

use App\Models\Inventory;
use Livewire\Component;

class ProductList extends Component
{
    public $sale_type;

    public $product_key;

    public $category_id;

    public $products = [];

    protected $listeners = [
        'Sale-getProducts-Component' => 'getProducts',
    ];

    public function mount()
    {
        $this->getProducts($this->sale_type, $this->category_id);
    }

    public function getProducts($sale_type = 'normal', $category_id = null, $product_key = null)
    {
        $this->products = Inventory::with('product')->join('products', 'product_id', 'products.id')
            ->when($product_key, function ($query, $value) {
                return $query->where('products.name', 'LIKE', '%'.$value.'%');
            })
            ->when($category_id, function ($query, $value) {
                if ($value == 'favorite') {
                    return $query->where('products.is_favorite', true);
                }

                return $query->where('products.main_category_id', $value);
            })
            ->where('inventories.branch_id', session('branch_id'))
            ->where('products.is_selling', true)
            ->orderBy('products.name')
            ->select(
                'inventories.id',
                'inventories.product_id',
                'inventories.quantity',
                'products.type',
                'products.name',
                'products.thumbnail',
            )
            ->limit(50)
            ->get()
            ->map(function ($item) use ($sale_type) {

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'type' => $item->type,
                    'quantity' => $item->quantity,
                    'name' => $item->name,
                    'mrp' => $item->product->saleTypePrice($sale_type),
                    'thumbnail' => $item->thumbnail,
                ];
            })
            ->toArray();
    }

    public function selectItem($id)
    {
        $this->dispatch('Sale-selectItem-Component', $id);
    }

    public function render()
    {
        return view('livewire.sale.product-list');
    }
}
