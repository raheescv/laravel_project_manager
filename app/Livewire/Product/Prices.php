<?php

namespace App\Livewire\Product;

use App\Actions\Product\ProductPrice\CreateAction;
use App\Actions\Product\ProductPrice\UpdateAction;
use App\Models\Product;
use App\Models\ProductPrice;
use Livewire\Component;

class Prices extends Component
{
    protected $listeners = [
        'Product-Prices-Create-Component' => 'create',
        'Product-Prices-Update-Component' => 'edit',
    ];

    public $units;

    public $product_prices;

    public $product_id;

    public $table_id;

    public function create()
    {
        $this->mount($this->product_id);
        $this->dispatch('ToggleProductPriceModal');
    }

    public function edit($id)
    {
        $this->mount($this->product_id, $id);
        $this->dispatch('ToggleProductPriceModal');
    }

    public function mount($product_id, $table_id = null)
    {

        $this->product_id = $product_id;
        $product = Product::find($this->product_id);
        if ($product) {
            $this->table_id = $table_id;
            if (! $this->table_id) {
                $this->product_prices = [
                    'product_id' => $product->id,
                    'price_type' => '',
                    'amount' => '',
                    'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d'),
                    'status' => 'active',
                ];
            } else {
                $product_prices = ProductPrice::find($this->table_id);
                $this->product_prices = $product_prices->toArray();
            }
        }
    }

    protected function rules()
    {
        return [
            'product_prices.product_id' => ['required'],
            'product_prices.amount' => ['required'],
            'product_prices.price_type' => ['required'],
        ];
    }

    protected $messages = [
        'product_prices.product_id.required' => 'The product field is required',
        'product_prices.amount.required' => 'The amount field is required',
        'product_prices.price_type.required' => 'The type is required',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->product_prices);
            } else {
                $response = (new UpdateAction())->execute($this->product_prices, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            if (! $close) {
                $this->dispatch('ToggleProductPriceModal');
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
        $priceTypes = priceTypes();
        unset($priceTypes['normal']);

        return view('livewire.product.prices', compact('priceTypes'));
    }
}
