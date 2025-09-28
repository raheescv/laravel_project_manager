<?php

namespace App\Livewire\Inventory\Barcode;

use App\Models\Inventory;
use Livewire\Component;

class CartPage extends Component
{
    public $selectedProductId = '';

    public $barcodeInput = '';

    public $quantity = 1;

    public $cartItems = [];

    public $searchQuery = '';

    public $products = [];

    public $showProductList = false;

    protected $listeners = [
        'productSelected' => 'addToCart',
        // 'barcodeScanned' => 'handleBarcodeScan'
    ];

    public function mount()
    {
        // Initialize cart from session if exists
        $this->cartItems = session('cart_items', []);

        // Set default quantity
        $this->quantity = 1;
    }

    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) >= 2) {
            $this->loadProducts();
        } else {
            $this->products = [];
        }
    }

    public function loadProducts()
    {
        $this->products = Inventory::with('product')
            ->whereHas('product', function ($query): void {
                $query->where('name', 'LIKE', '%'.$this->searchQuery.'%')
                    ->orWhere('barcode', 'LIKE', '%'.$this->searchQuery.'%')
                    ->orWhere('code', 'LIKE', '%'.$this->searchQuery.'%');
            })
            ->where('quantity', '>', 0)
            ->limit(10)
            ->get()
            ->map(function ($inventory) {
                return [
                    'id' => $inventory->id,
                    'product_id' => $inventory->product_id,
                    'name' => $inventory->product->name,
                    'barcode' => $inventory->barcode,
                    'mrp' => $inventory->product->mrp,
                    'size' => $inventory->product->size,
                    'quantity' => $inventory->quantity,
                    'image' => $inventory->product->thumbnail,
                    'type' => $inventory->product->type,
                ];
            })
            ->toArray();
    }

    public function selectProduct($productId)
    {
        $this->selectedProductId = $productId;
        $this->addToCart($productId);
        $this->searchQuery = '';
        $this->products = [];
    }

    public function handleBarcodeScan()
    {
        $barcode = $this->barcodeInput;
        if (empty($barcode)) {
            return;
        }

        $inventory = Inventory::with('product')->where('barcode', $barcode)->first();
        if ($inventory) {
            $this->addToCart($inventory->id);
            $this->barcodeInput = '';
            $this->dispatch('success', ['message' => 'Product added to cart via barcode scan.']);
        }
    }

    public function updatedBarcodeInput()
    {
        $this->handleBarcodeScan();
    }

    public function addToCart($inventoryId)
    {
        $inventory = Inventory::with('product')->find($inventoryId);

        if (! $inventory) {
            $this->dispatch('error', ['message' => 'Product not available or out of stock.']);

            return;
        }

        $cartKey = $inventory->id;

        if (isset($this->cartItems[$cartKey])) {
            $this->cartItems[$cartKey]['quantity'] += $this->quantity;
        } else {
            $this->cartItems[$cartKey] = [
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'name' => $inventory->product->name,
                'barcode' => $inventory->barcode,
                'size' => $inventory->product->size,
                'mrp' => $inventory->product->mrp,
                'quantity' => $this->quantity,
                'image' => $inventory->product->thumbnail,
                'type' => $inventory->product->type,
                'available_quantity' => $inventory->quantity,
            ];
        }

        // Update session
        session(['cart_items' => $this->cartItems]);
        $this->dispatch('success', ['message' => 'Product added to cart successfully.']);

        $this->quantity = 1;
        $this->selectedProductId = '';

        // Clear search results after adding to cart
        $this->products = [];
    }

    public function updateQuantity($cartKey, $newQuantity)
    {
        if ($newQuantity <= 0) {
            unset($this->cartItems[$cartKey]);
        } else {
            $this->cartItems[$cartKey]['quantity'] = $newQuantity;
        }

        session(['cart_items' => $this->cartItems]);
        $this->dispatch('success', ['message' => 'Cart updated successfully.']);
    }

    public function removeFromCart($cartKey)
    {
        unset($this->cartItems[$cartKey]);
        session(['cart_items' => $this->cartItems]);
        $this->dispatch('success', ['message' => 'Product removed from cart.']);
    }

    public function clearCart()
    {
        $this->cartItems = [];
        session()->forget('cart_items');
        $this->dispatch('success', ['message' => 'Cart cleared successfully.']);
    }

    public function getTotalQuantity()
    {
        return collect($this->cartItems)->sum('quantity');
    }

    public function printBarcodes()
    {
        if (empty($this->cartItems)) {
            $this->dispatch('error', ['message' => 'Cart is empty. Please add products first.']);

            return;
        }

        // Store cart items in session for printing
        session(['print_cart_items' => $this->cartItems]);

        // Redirect to print page using the existing barcode print method
        return redirect()->route('inventory::barcode::cart::print');
    }

    public function render()
    {
        return view('livewire.inventory.barcode.cart-page');
    }
}
