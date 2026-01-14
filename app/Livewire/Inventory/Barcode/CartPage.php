<?php

namespace App\Livewire\Inventory\Barcode;

use App\Models\Inventory;
use App\Models\ProductUnit;
use App\Models\Unit;
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

    public $selectedUnitId = '';

    protected $listeners = [
        'productSelected' => 'addToCart',
        // 'barcodeScanned' => 'handleBarcodeScan'
    ];

    public function mount()
    {
        // Initialize cart from session if exists
        $this->cartItems = session('cart_items', []);
        $this->cartItems = $this->sortCartItemsByProductId($this->cartItems);

        // Set default quantity
        $this->quantity = 1;
    }

    private function sortCartItemsByProductId($cartItems)
    {
        if (empty($cartItems)) {
            return $cartItems;
        }

        uasort($cartItems, function ($a, $b) {
            return $a['product_id'] <=> $b['product_id'];
        });

        return $cartItems;
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
        $products = collect();

        // Load Inventory items
        $inventories = Inventory::with('product')
            ->whereHas('product', function ($query): void {
                $query
                    ->where('name', 'LIKE', '%' . $this->searchQuery . '%')
                    ->orWhere('barcode', 'LIKE', '%' . $this->searchQuery . '%')
                    ->orWhere('code', 'LIKE', '%' . $this->searchQuery . '%');
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
                    'item_type' => 'inventory',
                ];
            });

        $products = $products->merge($inventories);

        // Load ProductUnit items
        $productUnitsQuery = ProductUnit::with('product', 'subUnit')
            ->where(function ($query) {
                $query->whereHas('product', function ($q): void {
                    $q->where('name', 'LIKE', '%' . $this->searchQuery . '%')
                      ->orWhere('code', 'LIKE', '%' . $this->searchQuery . '%');
                })
                ->orWhere('barcode', 'LIKE', '%' . $this->searchQuery . '%');
            });

        // Apply unit filter if selected
        if (!empty($this->selectedUnitId)) {
            $productUnitsQuery->where('sub_unit_id', $this->selectedUnitId);
        }

        $productUnits = $productUnitsQuery->limit(10)->get()
            ->map(function ($productUnit) {
                return [
                    'id' => $productUnit->id,
                    'product_id' => $productUnit->product_id,
                    'name' => $productUnit->product->name . ' (' . ($productUnit->subUnit->name ?? 'N/A') . ')',
                    'barcode' => $productUnit->barcode,
                    'mrp' => $productUnit->product->mrp,
                    'size' => $productUnit->product->size,
                    'quantity' => 0, // ProductUnit doesn't have quantity
                    'image' => $productUnit->product->thumbnail,
                    'type' => $productUnit->product->type,
                    'item_type' => 'product_unit',
                    'conversion_factor' => $productUnit->conversion_factor,
                    'sub_unit_name' => $productUnit->subUnit->name ?? 'N/A',
                ];
            });

        $products = $products->merge($productUnits);

        $this->products = $products->take(20)->toArray();
    }

    public function selectProduct($productId, $itemType = 'inventory')
    {
        $this->selectedProductId = $productId;
        $this->addToCart($productId, false, $itemType);
        $this->searchQuery = '';
        $this->products = [];
    }

    public function addAllInventory()
    {
        $addedCount = 0;
        $skippedCount = 0;

        // Add all Inventory items
        $inventories = Inventory::with('product')->get();
        foreach ($inventories as $inventory) {
            $this->addToCart($inventory->id, true, 'inventory'); // Suppress individual messages
            $addedCount++;
        }

        // Update session after all additions
        $this->cartItems = $this->sortCartItemsByProductId($this->cartItems);
        session(['cart_items' => $this->cartItems]);

        if ($addedCount > 0) {
            $message = "Successfully added {$addedCount} inventory item(s) to cart.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} item(s) were skipped.";
            }
            $this->dispatch('success', ['message' => $message]);
        } else {
            $this->dispatch('error', ['message' => 'No inventory items could be added to cart.']);
        }

        $this->searchQuery = '';
        $this->products = [];
    }

    public function addAllProductUnits()
    {
        $addedCount = 0;
        $skippedCount = 0;

        // Build query for ProductUnit items
        $productUnitsQuery = ProductUnit::with('product', 'subUnit');

        // Filter by selected unit if provided
        if (!empty($this->selectedUnitId)) {
            $productUnitsQuery->where('sub_unit_id', $this->selectedUnitId);
        }

        $productUnits = $productUnitsQuery->get();

        foreach ($productUnits as $productUnit) {
            $this->addToCart($productUnit->id, true, 'product_unit'); // Suppress individual messages
            $addedCount++;
        }

        // Update session after all additions
        $this->cartItems = $this->sortCartItemsByProductId($this->cartItems);
        session(['cart_items' => $this->cartItems]);

        if ($addedCount > 0) {
            $unitFilter = !empty($this->selectedUnitId) ? ' (filtered by unit)' : '';
            $message = "Successfully added {$addedCount} product unit(s) to cart{$unitFilter}.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} item(s) were skipped.";
            }
            $this->dispatch('success', ['message' => $message]);
        } else {
            $unitFilter = !empty($this->selectedUnitId) ? ' for the selected unit' : '';
            $this->dispatch('error', ['message' => "No product units could be added to cart{$unitFilter}."]);
        }

        $this->searchQuery = '';
        $this->products = [];
    }

    public function getUnitsProperty()
    {
        return Unit::orderBy('name')->get();
    }

    public function handleBarcodeScan()
    {
        $barcode = $this->barcodeInput;
        if (empty($barcode)) {
            return;
        }

        // First try to find in Inventory
        $inventory = Inventory::with('product')->where('barcode', $barcode)->first();
        if ($inventory) {
            $this->addToCart($inventory->id, false, 'inventory');
            $this->barcodeInput = '';
            $this->dispatch('success', ['message' => 'Product added to cart via barcode scan.']);
            return;
        }

        // Then try to find in ProductUnit
        $productUnit = ProductUnit::with('product', 'subUnit')->where('barcode', $barcode)->first();
        if ($productUnit) {
            $this->addToCart($productUnit->id, false, 'product_unit');
            $this->barcodeInput = '';
            $this->dispatch('success', ['message' => 'Product unit added to cart via barcode scan.']);
            return;
        }

        $this->dispatch('error', ['message' => 'Barcode not found.']);
    }

    public function updatedBarcodeInput()
    {
        $this->handleBarcodeScan();
    }

    public function addToCart($itemId, $suppressMessage = false, $itemType = 'inventory')
    {
        if ($itemType === 'product_unit') {
            $productUnit = ProductUnit::with('product', 'subUnit')->find($itemId);

            if (!$productUnit) {
                if (!$suppressMessage) {
                    $this->dispatch('error', ['message' => 'Product unit not found.']);
                }
                return;
            }

            $cartKey = 'product_unit_' . $productUnit->id;

            if (isset($this->cartItems[$cartKey])) {
                $this->cartItems[$cartKey]['quantity'] += $this->quantity;
            } else {
                $this->cartItems[$cartKey] = [
                    'item_type' => 'product_unit',
                    'product_unit_id' => $productUnit->id,
                    'product_id' => $productUnit->product_id,
                    'name' => $productUnit->product->name . ' (' . ($productUnit->subUnit->name ?? 'N/A') . ')',
                    'barcode' => $productUnit->barcode,
                    'size' => $productUnit->product->size,
                    'mrp' => $productUnit->product->mrp,
                    'quantity' => $this->quantity,
                    'image' => $productUnit->product->thumbnail,
                    'type' => $productUnit->product->type,
                    'conversion_factor' => $productUnit->conversion_factor,
                    'sub_unit_name' => $productUnit->subUnit->name ?? 'N/A',
                ];
            }

            // Update session
            $this->cartItems = $this->sortCartItemsByProductId($this->cartItems);
            session(['cart_items' => $this->cartItems]);

            if (!$suppressMessage) {
                $this->dispatch('success', ['message' => 'Product unit added to cart successfully.']);
            }

            $this->quantity = 1;
            $this->selectedProductId = '';

            // Clear search results after adding to cart
            if (!$suppressMessage) {
                $this->products = [];
            }

            return;
        }

        // Handle Inventory items (existing logic)
        $inventory = Inventory::with('product')->find($itemId);

        if (!$inventory) {
            if (!$suppressMessage) {
                $this->dispatch('error', ['message' => 'Product not available or out of stock.']);
            }

            return;
        }

        $cartKey = 'inventory_' . $inventory->id;

        if (isset($this->cartItems[$cartKey])) {
            $this->cartItems[$cartKey]['quantity'] += $this->quantity;
        } else {
            $this->cartItems[$cartKey] = [
                'item_type' => 'inventory',
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
        $this->cartItems = $this->sortCartItemsByProductId($this->cartItems);
        session(['cart_items' => $this->cartItems]);

        if (!$suppressMessage) {
            $this->dispatch('success', ['message' => 'Product added to cart successfully.']);
        }

        $this->quantity = 1;
        $this->selectedProductId = '';

        // Clear search results after adding to cart
        if (!$suppressMessage) {
            $this->products = [];
        }
    }

    public function updateQuantity($cartKey, $newQuantity)
    {
        if ($newQuantity <= 0) {
            unset($this->cartItems[$cartKey]);
        } else {
            $this->cartItems[$cartKey]['quantity'] = $newQuantity;
        }

        $this->cartItems = $this->sortCartItemsByProductId($this->cartItems);
        session(['cart_items' => $this->cartItems]);
        $this->dispatch('success', ['message' => 'Cart updated successfully.']);
    }

    public function removeFromCart($cartKey)
    {
        unset($this->cartItems[$cartKey]);
        $this->cartItems = $this->sortCartItemsByProductId($this->cartItems);
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
