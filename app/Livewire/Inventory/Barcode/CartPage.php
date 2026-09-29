<?php

namespace App\Livewire\Inventory\Barcode;

use App\Models\Configuration;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Support\BarcodeLabel;
use App\Support\BarcodeTemplateConfiguration;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
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

    /**
     * The label template the batch prints with. Its `cart` switches decide
     * whether rows carry their own price, grams instead of a count, and a new
     * row per scan.
     */
    public string $templateKey = '';

    public string $selectedRowKey = '';

    /**
     * Console switch: scanning an item already in the cart adds a new row for
     * it instead of raising that row's label count. Saved per tenant.
     */
    public bool $separateRows = false;

    /**
     * Console switch: on (Auto), a new row starts with the product MRP and, in
     * weight mode, 1 g; off (Custom), both start blank and every row must be
     * filled in before printing. Saved per tenant.
     */
    public bool $autoFill = true;

    public $fillPrice = '';

    public $fillWeight = '';

    public $fillUnitPrice = '';

    public $fillTax = '';

    protected $listeners = [
        'productSelected' => 'addToCart',
        // 'barcodeScanned' => 'handleBarcodeScan'
    ];

    public function mount()
    {
        // Initialize cart from session if exists
        $this->cartItems = array_map(fn (array $item): array => $item + [
            'price' => round((float) ($item['mrp'] ?? 0) * (float) ($item['conversion_factor'] ?? 1), 2),
            'weight' => null,
            'unit_price' => null,
            'tax' => 0,
        ], session('cart_items', []));
        $this->cartItems = $this->withCategoryNames($this->cartItems);
        $this->cartItems = $this->sortCartItemsByProductId($this->cartItems);

        $templates = $this->templates();
        $savedTemplateKey = (string) session('barcode_cart_template', '');
        $this->templateKey = isset($templates[$savedTemplateKey])
            ? $savedTemplateKey
            : BarcodeTemplateConfiguration::getConfiguration()['default_template'];

        $this->separateRows = Configuration::where('key', 'barcode_cart_separate_rows')->value('value') === '1';
        $this->autoFill = Configuration::where('key', 'barcode_cart_auto_fill')->value('value') !== '0';

        // Set default quantity
        $this->quantity = 1;
    }

    /**
     * @return array<string, array{name: string, type: string}>
     */
    #[Computed]
    public function templates(): array
    {
        return collect(BarcodeTemplateConfiguration::getConfiguration()['templates'])
            ->map(fn (array $template): array => ['name' => $template['name'], 'type' => $template['type']])
            ->all();
    }

    /**
     * `separate_rows` from the console switch, `weight_mode` from the sale
     * setting "Quantity Label In Print": with Weight, every row takes its grams.
     *
     * `category_name` from "Item Label In Print": rows are titled with the
     * name their label prints.
     *
     * `price_column` and `weight_column` show the MRP and Weight inputs only
     * when the chosen template prints a price and a Qty line.
     *
     * @return array{separate_rows: bool, auto_fill: bool, weight_mode: bool, category_name: bool, price_column: bool, weight_column: bool}
     */
    #[Computed]
    public function cartSettings(): array
    {
        $settings = BarcodeTemplateConfiguration::resolveSettings($this->templateKey)['settings'];
        $weightMode = BarcodeLabel::usesWeight();

        return [
            'separate_rows' => $this->separateRows,
            'auto_fill' => $this->autoFill,
            'weight_mode' => $weightMode,
            'category_name' => BarcodeLabel::usesCategoryName(),
            'price_column' => BarcodeLabel::printsPrice($settings),
            'weight_column' => $weightMode && BarcodeLabel::printsQty($settings),
        ];
    }

    public function updatedSeparateRows(): void
    {
        unset($this->cartSettings);
        Configuration::updateOrCreate(['key' => 'barcode_cart_separate_rows'], ['value' => $this->separateRows ? '1' : '0']);
    }

    /**
     * The switch applies to rows already in the cart too: Auto fills their
     * blank MRP and grams, Custom blanks the values Auto put there and keeps
     * anything typed by hand.
     */
    public function updatedAutoFill(): void
    {
        unset($this->cartSettings);
        Configuration::updateOrCreate(['key' => 'barcode_cart_auto_fill'], ['value' => $this->autoFill ? '1' : '0']);

        $autoWeight = $this->cartSettings()['weight_mode'] ? 1.0 : null;

        foreach ($this->cartItems as $rowKey => $item) {
            $autoPrice = $this->mrpPrice($item);

            if ($this->autoFill) {
                $this->cartItems[$rowKey]['price'] = is_numeric($item['price'] ?? null) ? $item['price'] : $autoPrice;
                $this->cartItems[$rowKey]['weight'] = ($item['weight'] ?? null) ?: $autoWeight;

                continue;
            }

            if (is_numeric($item['price'] ?? null) && (float) $item['price'] === $autoPrice) {
                $this->cartItems[$rowKey]['price'] = null;
            }
            if ($autoWeight !== null && (float) ($item['weight'] ?? 0) === $autoWeight) {
                $this->cartItems[$rowKey]['weight'] = null;
            }
        }

        $this->persistCart();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function mrpPrice(array $item): float
    {
        return round((float) ($item['mrp'] ?? 0) * (float) ($item['conversion_factor'] ?? 1), 2);
    }

    /**
     * With a unit price, the MRP is worked out: unit price × weight (grams in
     * weight mode, else 1) plus tax. Without one, the MRP stays as typed.
     *
     * @param  array<string, mixed>  $item
     */
    private function calculatedPrice(array $item): ?float
    {
        if (! is_numeric($item['unit_price'] ?? null)) {
            return null;
        }

        $weight = $this->cartSettings()['weight_column'] ? (float) ($item['weight'] ?? 0) : 1.0;

        return round((float) $item['unit_price'] * $weight * (1 + (float) ($item['tax'] ?? 0) / 100), 2);
    }

    private function recalculatePrice(string $rowKey): void
    {
        $price = $this->calculatedPrice($this->cartItems[$rowKey]);
        if ($price !== null) {
            $this->cartItems[$rowKey]['price'] = $price;
        }
    }

    public function updatedTemplateKey(): void
    {
        if (! isset($this->templates()[$this->templateKey])) {
            $this->templateKey = BarcodeTemplateConfiguration::getConfiguration()['default_template'];
        }

        unset($this->cartSettings);
        session(['barcode_cart_template' => $this->templateKey]);
    }

    /**
     * Rows saved before the cart carried a category get theirs filled in, in one query.
     *
     * @param  array<string, array<string, mixed>>  $cartItems
     * @return array<string, array<string, mixed>>
     */
    private function withCategoryNames(array $cartItems): array
    {
        $productIds = collect($cartItems)->reject(fn (array $item): bool => array_key_exists('category_name', $item))->pluck('product_id')->unique();
        if ($productIds->isEmpty()) {
            return $cartItems;
        }

        $categoryNames = Product::with('mainCategory:id,name')->whereIn('id', $productIds)->get(['id', 'main_category_id'])
            ->mapWithKeys(fn (Product $product): array => [$product->id => $product->mainCategory?->name]);

        return array_map(fn (array $item): array => $item + ['category_name' => $categoryNames[$item['product_id']] ?? null], $cartItems);
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

    public function updatedBarcodeInput()
    {
        if (strlen(trim($this->barcodeInput)) >= 2) {
            $this->loadProducts(trim($this->barcodeInput), true);
        } else {
            $this->products = [];
        }
    }

    public function updatedSearchQuery()
    {
        if (strlen(trim($this->searchQuery)) >= 2) {
            $this->loadProducts(trim($this->searchQuery));
        } else {
            $this->products = [];
        }
    }

    // The scanner box matches barcodes only; the search box also matches product name and code
    public function loadProducts(string $term, bool $barcodeOnly = false)
    {
        $like = '%'.$term.'%';
        $products = collect();
        // Load Inventory items
        $inventories = Inventory::with('product')
            ->where(function ($query) use ($like, $barcodeOnly): void {
                $query->where('inventories.barcode', 'LIKE', $like);
                if (! $barcodeOnly) {
                    $query->orWhereHas('product', function ($q) use ($like): void {
                        $q->where('name', 'LIKE', $like)
                            ->orWhere('barcode', 'LIKE', $like)
                            ->orWhere('code', 'LIKE', $like);
                    });
                }
            })
            ->where('inventories.branch_id', session('branch_id'))
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
            ->where(function ($query) use ($like, $barcodeOnly): void {
                $query->where('product_units.barcode', 'LIKE', $like);
                if (! $barcodeOnly) {
                    $query->orWhereHas('product', function ($q) use ($like): void {
                        $q->where('name', 'LIKE', $like)
                            ->orWhere('code', 'LIKE', $like);
                    });
                }
            });

        // Apply unit filter if selected
        if (! empty($this->selectedUnitId)) {
            $productUnitsQuery->where('sub_unit_id', $this->selectedUnitId);
        }

        $productUnits = $productUnitsQuery->limit(10)->get()
            ->map(function ($productUnit) {
                return [
                    'id' => $productUnit->id,
                    'product_id' => $productUnit->product_id,
                    'name' => $productUnit->product->name.' ('.($productUnit->subUnit->name ?? 'N/A').')',
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
        if (! empty($this->selectedUnitId)) {
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
            $unitFilter = ! empty($this->selectedUnitId) ? ' (filtered by unit)' : '';
            $message = "Successfully added {$addedCount} product unit(s) to cart{$unitFilter}.";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} item(s) were skipped.";
            }
            $this->dispatch('success', ['message' => $message]);
        } else {
            $unitFilter = ! empty($this->selectedUnitId) ? ' for the selected unit' : '';
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
        $inventory = Inventory::with('product')->where('barcode', $barcode)->where('branch_id', session('branch_id'))->first();
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

    public function addToCart($itemId, $suppressMessage = false, $itemType = 'inventory')
    {
        if ($itemType === 'product_unit') {
            $productUnit = ProductUnit::with('product.mainCategory', 'subUnit')->find($itemId);

            if (! $productUnit) {
                if (! $suppressMessage) {
                    $this->dispatch('error', ['message' => 'Product unit not found.']);
                }

                return;
            }

            $added = $this->putRow('product_unit_'.$productUnit->id, [
                'item_type' => 'product_unit',
                'product_unit_id' => $productUnit->id,
                'product_id' => $productUnit->product_id,
                'name' => $productUnit->product->name.' ('.($productUnit->subUnit->name ?? 'N/A').')',
                'category_name' => $productUnit->product->mainCategory?->name,
                'barcode' => $productUnit->barcode,
                'size' => $productUnit->product->size,
                'mrp' => $productUnit->product->mrp,
                'unit_price' => null,
                'tax' => (float) $productUnit->product->tax,
                'price' => round((float) $productUnit->product->mrp * (float) $productUnit->conversion_factor, 2),
                'image' => $productUnit->product->thumbnail,
                'type' => $productUnit->product->type,
                'conversion_factor' => $productUnit->conversion_factor,
                'sub_unit_name' => $productUnit->subUnit->name ?? 'N/A',
            ], $suppressMessage);

            $this->afterAdd($added, $suppressMessage, 'Product unit added to cart successfully.');

            return;
        }

        $inventory = Inventory::with('product.mainCategory')->find($itemId);

        if (! $inventory) {
            if (! $suppressMessage) {
                $this->dispatch('error', ['message' => 'Product not available or out of stock.']);
            }

            return;
        }

        $added = $this->putRow('inventory_'.$inventory->id, [
            'item_type' => 'inventory',
            'inventory_id' => $inventory->id,
            'product_id' => $inventory->product_id,
            'name' => $inventory->product->name,
            'category_name' => $inventory->product->mainCategory?->name,
            'barcode' => $inventory->barcode,
            'size' => $inventory->product->size,
            'mrp' => $inventory->product->mrp,
            'unit_price' => null,
            'tax' => (float) $inventory->product->tax,
            'price' => round((float) $inventory->product->mrp, 2),
            'image' => $inventory->product->thumbnail,
            'type' => $inventory->product->type,
            'available_quantity' => $inventory->quantity,
        ], $suppressMessage);

        $this->afterAdd($added, $suppressMessage, 'Product added to cart successfully.');
    }

    /**
     * Put one item into the cart. With separate rows on, an item already in the
     * cart gets a new row of its own; otherwise its row's label count goes up.
     *
     * @param  array<string, mixed>  $row
     */
    private function putRow(string $itemKey, array $row, bool $suppressMessage): bool
    {
        $cart = $this->cartSettings();
        $existingKey = collect($this->cartItems)->search(fn (array $item, string $key): bool => $this->itemKey($key) === $itemKey);

        if ($existingKey !== false && ! $cart['separate_rows']) {
            $this->cartItems[$existingKey]['quantity'] += $this->quantity;
            $this->selectedRowKey = $existingKey;

            return true;
        }

        $rowKey = $existingKey === false ? $itemKey : $itemKey.'__'.Str::lower(Str::random(6));
        $this->cartItems[$rowKey] = array_merge($row, [
            'price' => $cart['auto_fill'] ? $row['price'] : null,
            'quantity' => $this->quantity,
            'weight' => $this->startingWeight(),
        ]);
        $this->selectedRowKey = $rowKey;

        return true;
    }

    /**
     * The grams a new row starts with: 1 when auto filling in weight mode.
     */
    private function startingWeight(): ?float
    {
        $cart = $this->cartSettings();

        return $cart['auto_fill'] && $cart['weight_mode'] ? 1.0 : null;
    }

    /**
     * The item a row belongs to: `inventory_12` for both `inventory_12` and its
     * extra rows `inventory_12__ab3kq9`.
     */
    private function itemKey(string $rowKey): string
    {
        return Str::before($rowKey, '__');
    }

    private function afterAdd(bool $added, bool $suppressMessage, string $message): void
    {
        $this->persistCart();

        if ($added && ! $suppressMessage) {
            $this->dispatch('success', ['message' => $message]);
        }

        $this->quantity = 1;
        $this->selectedProductId = '';

        if (! $suppressMessage) {
            $this->products = [];
        }
    }

    private function persistCart(): void
    {
        $this->cartItems = $this->sortCartItemsByProductId($this->cartItems);
        session(['cart_items' => $this->cartItems]);
    }

    /**
     * Rows are edited in place (price, grams, count); keep each edit sane and saved.
     */
    public function updatedCartItems($value, $key): void
    {
        [$rowKey, $field] = array_pad(explode('.', (string) $key, 2), 2, null);

        if (! isset($this->cartItems[$rowKey])) {
            return;
        }

        $row = &$this->cartItems[$rowKey];

        match ($field) {
            'price' => $row['price'] = match (true) {
                is_numeric($value) && $value >= 0 => round((float) $value, 2),
                $this->autoFill => $this->mrpPrice($row),
                default => null,
            },
            'weight' => $row['weight'] = is_numeric($value) && $value > 0 ? round((float) $value, 3) : null,
            'unit_price' => $row['unit_price'] = is_numeric($value) && $value >= 0 ? round((float) $value, 2) : null,
            'tax' => $row['tax'] = is_numeric($value) && $value >= 0 ? round((float) $value, 2) : 0,
            'quantity' => $row['quantity'] = max(1, (int) $value),
            default => null,
        };

        unset($row);

        if (in_array($field, ['weight', 'unit_price', 'tax'], true)) {
            $this->recalculatePrice($rowKey);
        }
        session(['cart_items' => $this->cartItems]);
    }

    public function selectRow(string $rowKey): void
    {
        if (isset($this->cartItems[$rowKey])) {
            $this->selectedRowKey = $rowKey;
        }
    }

    /**
     * Another row for the same item, e.g. the next piece of the same design.
     */
    public function duplicateRow(string $rowKey): void
    {
        if (! isset($this->cartItems[$rowKey])) {
            return;
        }

        $newKey = $this->itemKey($rowKey).'__'.Str::lower(Str::random(6));
        $this->cartItems[$newKey] = array_merge($this->cartItems[$rowKey], ['weight' => $this->startingWeight()]);
        $this->recalculatePrice($newKey);
        $this->selectedRowKey = $newKey;
        $this->persistCart();
    }

    /**
     * Custom fill: one MRP, weight, unit price and/or tax typed once, applied to
     * every row. Rows with a unit price get their MRP worked out again.
     */
    public function fillAllRows(): void
    {
        $price = is_numeric($this->fillPrice) && $this->fillPrice >= 0 ? round((float) $this->fillPrice, 2) : null;
        $weight = is_numeric($this->fillWeight) && $this->fillWeight > 0 && $this->cartSettings()['weight_column'] ? round((float) $this->fillWeight, 3) : null;
        $unitPrice = is_numeric($this->fillUnitPrice) && $this->fillUnitPrice >= 0 ? round((float) $this->fillUnitPrice, 2) : null;
        $tax = is_numeric($this->fillTax) && $this->fillTax >= 0 ? round((float) $this->fillTax, 2) : null;

        if ($price === null && $weight === null && $unitPrice === null && $tax === null) {
            $this->dispatch('error', ['message' => 'Enter an MRP, weight, unit price or tax to fill.']);

            return;
        }

        foreach ($this->cartItems as $rowKey => $item) {
            if ($price !== null) {
                $this->cartItems[$rowKey]['price'] = $price;
            }
            if ($weight !== null) {
                $this->cartItems[$rowKey]['weight'] = $weight;
            }
            if ($unitPrice !== null) {
                $this->cartItems[$rowKey]['unit_price'] = $unitPrice;
            }
            if ($tax !== null) {
                $this->cartItems[$rowKey]['tax'] = $tax;
            }
            if ($weight !== null || $unitPrice !== null || $tax !== null) {
                $this->recalculatePrice($rowKey);
            }
        }

        $this->fillPrice = '';
        $this->fillWeight = '';
        $this->fillUnitPrice = '';
        $this->fillTax = '';
        $this->persistCart();
        $this->dispatch('success', ['message' => 'Filled '.count($this->cartItems).' row(s).']);
    }

    public function updateQuantity($cartKey, $newQuantity)
    {
        if ($newQuantity <= 0) {
            unset($this->cartItems[$cartKey]);
        } else {
            $this->cartItems[$cartKey]['quantity'] = $newQuantity;
        }

        $this->persistCart();
    }

    public function removeFromCart($cartKey)
    {
        unset($this->cartItems[$cartKey]);
        if ($this->selectedRowKey === $cartKey) {
            $this->selectedRowKey = '';
        }
        $this->persistCart();
        $this->dispatch('success', ['message' => 'Product removed from cart.']);
    }

    public function clearCart()
    {
        $this->cartItems = [];
        $this->selectedRowKey = '';
        session()->forget('cart_items');
        $this->dispatch('success', ['message' => 'Cart cleared successfully.']);
    }

    public function getTotalQuantity()
    {
        return collect($this->cartItems)->sum('quantity');
    }

    public function getTotalWeight(): float
    {
        return (float) collect($this->cartItems)->sum(fn (array $item): float => (float) ($item['weight'] ?? 0));
    }

    public function printBarcodes()
    {
        if (empty($this->cartItems)) {
            $this->dispatch('error', ['message' => 'Cart is empty. Please add products first.']);

            return;
        }

        $cart = $this->cartSettings();
        $printItems = $this->cartItems;

        $missingPrice = collect($printItems)->filter(fn (array $item): bool => $cart['price_column'] && ! is_numeric($item['price'] ?? null));
        if ($missingPrice->isNotEmpty()) {
            $this->selectedRowKey = (string) $missingPrice->keys()->first();
            $this->dispatch('error', ['message' => "Enter the MRP for every row ({$missingPrice->count()} missing)."]);

            return;
        }

        if ($cart['weight_column']) {
            $missing = collect($printItems)->filter(fn (array $item): bool => ! is_numeric($item['weight'] ?? null) || (float) $item['weight'] <= 0);
            if ($missing->isNotEmpty()) {
                $this->selectedRowKey = (string) $missing->keys()->first();
                $this->dispatch('error', ['message' => "Enter the weight for every row ({$missing->count()} missing)."]);

                return;
            }
        }

        session(['print_cart_items' => $printItems]);

        // The page sends the PDF to QZ Tray, or opens it when QZ Tray isn't set up
        $this->dispatch('label-print', url: route('inventory::barcode::cart::print', ['template' => $this->templateKey]));
    }

    public function render()
    {
        return view('livewire.inventory.barcode.cart-page');
    }
}
