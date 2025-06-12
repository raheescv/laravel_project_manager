<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\CreateAction;
use App\Actions\Sale\Item\DeleteAction as ItemDeleteAction;
use App\Actions\Sale\Payment\DeleteAction as PaymentDeleteAction;
use App\Actions\Sale\UpdateAction;
use App\Helpers\Facades\SaleHelper;
use App\Helpers\Facades\WhatsappHelper;
use App\Models\Account;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Sale-Custom-Payment-Confirmed' => 'collectPayments',
        'Sale-Edited-Items-Component' => 'editedItems',
        'Sale-Edited-Item-Component' => 'editedItem',
        'Sale-selectItem-Component' => 'selectItem',
        'Sale-Delete-Sync-Items-Component' => 'removeSyncItemFromViewItem',
        'Sale-ComboOffer-Update-Price' => 'updateComboOfferItemPrice',
        'Save-Sale-Feedback' => 'saveFeedback',
    ];

    public $categories;

    public $categoryCount;

    public $products;

    public $barcode_key;

    public $product_key;

    public $category_id = 'favorite';

    public $category_key;

    public $table_id;

    public $account_balance;

    public $accounts;

    public $inventory_id;

    public $employee;

    public $employee_id;

    public $employees = [];

    public $send_to_whatsapp;

    public $items = [];

    public $payment = [];

    public $payments = [];

    public $paymentMethods = [];

    public $payment_method_name;

    public $sale;

    public $sales = [];

    public $comboOffers = [];

    public $default_payment_method_id = 1;

    // Add new properties for caching
    protected $cachePrefix = 'sale_page_';

    protected $cacheTtl = 3600; // 1 hour

    protected $cacheTtlShort = 300; // 5 minutes

    // Add computed properties to reduce calculations
    protected $computedTotals = null;

    protected $computedItems = null;

    public function mount($table_id = null)
    {
        $this->category_id = 'favorite';
        $this->table_id = $table_id;

        // Batch load all required data in parallel
        $this->loadInitialData();

        if ($this->table_id) {
            $this->loadSaleData();
        } else {
            $this->initializeNewSale();
        }

        $this->getCustomerDetails();
        $this->dispatch('SelectDropDownValues', $this->sales);
        $this->getCategories();
    }

    protected function getCachedData($key, $ttl, $callback)
    {
        $serialized = Redis::get($key);
        if ($serialized) {
            return unserialize($serialized);
        }

        $data = $callback();
        Redis::setex($key, $ttl, serialize($data));

        return $data;
    }

    protected function loadInitialData()
    {
        // Load all initial data in parallel using Redis
        $data = Redis::pipeline(function ($pipe) {
            $pipe->get($this->cachePrefix.'payment_methods');
            $pipe->get($this->cachePrefix.'employees');
            $pipe->get($this->cachePrefix.'default_payment_method_id');
        });

        // Set payment methods
        $this->paymentMethods = $this->getCachedData(
            $this->cachePrefix.'payment_methods',
            $this->cacheTtl,
            fn () => Account::where('id', $this->default_payment_method_id)
                ->pluck('name', 'id')
                ->toArray()
        );

        // Set employees
        if (User::employee()->count() > 0) {
            $this->employees = $this->getCachedData(
                $this->cachePrefix.'employees',
                $this->cacheTtl,
                fn () => User::employee()->pluck('name', 'id')->toArray()
            );
            $this->employee_id = User::employee()->first(['id'])->id;
        }

        // Set default payment method
        $this->default_payment_method_id = $this->getCachedData(
            $this->cachePrefix.'default_payment_method_id',
            $this->cacheTtl,
            fn () => Configuration::where('key', 'default_payment_method_id')->value('value') ?? 1
        );

        $this->payment_method_name = strtolower(Account::find($this->default_payment_method_id)->name);
        $this->payment = [
            'payment_method_id' => $this->default_payment_method_id,
            'payment_method_name' => $this->payment_method_name,
            'amount' => 0,
            'name' => null,
        ];
    }

    protected function initializeNewSale()
    {
        // Cache account data for 1 hour
        $this->accounts = $this->getCachedData(
            $this->cachePrefix.'accounts_default',
            $this->cacheTtl,
            fn () => Account::where('id', 3)->pluck('name', 'id')->toArray()
        );

        // Initialize empty arrays
        $this->items = [];
        $this->payments = [];
        $this->comboOffers = [];

        // Initialize sales data with default values
        $this->sales = [
            'date' => now()->format('Y-m-d'),
            'due_date' => now()->format('Y-m-d'),
            'sale_type' => 'normal',
            'account_id' => 3,
            'customer_name' => '',
            'customer_mobile' => '',

            // Financial fields
            'gross_amount' => 0,
            'total_quantity' => 0,
            'item_discount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'other_discount' => 0,
            'freight' => 0,
            'grand_total' => 0,
            'paid' => 0,
            'balance' => 0,

            // Additional fields
            'address' => null,
            'rating' => 0,
            'feedback_type' => 'compliment',
            'feedback' => null,
            'status' => 'draft',
        ];

        // Set test customer mobile in non-production environment
        if (! app()->isProduction()) {
            $this->sales['customer_mobile'] = '+919633155669';
        }

        // Initialize payment data
        $this->payment = [
            'payment_method_id' => $this->default_payment_method_id,
            'payment_method_name' => $this->payment_method_name,
            'amount' => 0,
            'name' => null,
        ];

        // Reset computed properties
        $this->computedTotals = null;
        $this->computedItems = null;
    }

    protected function loadSaleData()
    {
        // Load sale data with all relationships in a single query
        $this->sale = $this->getCachedData(
            $this->cachePrefix."sale_{$this->table_id}",
            $this->cacheTtlShort,
            fn () => Sale::with([
                'account:id,name,mobile',
                'branch:id,name',
                'items' => function ($query) {
                    $query->with([
                        'product:id,name,mrp',
                        'employee:id,name',
                        'assistant:id,name',
                    ]);
                },
                'comboOffers.comboOffer:id,name',
                'createdUser:id,name',
                'updatedUser:id,name',
                'cancelledUser:id,name',
                'payments.paymentMethod:id,name',
            ])->find($this->table_id)
        );

        if (! $this->sale) {
            return redirect()->route('sale::index');
        }

        $this->processSaleData();
    }

    protected function processSaleData()
    {
        $this->sales = $this->sale->toArray();

        // Process items in batch
        $this->items = $this->processItems($this->sale->items);

        // Process combo offers in batch
        $this->comboOffers = $this->processComboOffers($this->sale->comboOffers);

        // Process payments
        $this->processPayments();

        $this->mainCalculator();
    }

    protected function processPayments()
    {
        if (! $this->sale) {
            return;
        }

        $this->payments = $this->sale->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'payment_method_id' => $payment->payment_method_id,
                'payment_method_name' => $payment->paymentMethod->name,
                'amount' => $payment->amount,
                'date' => $payment->date,
                'name' => $payment->name,
                'created_by' => $payment->created_by,
            ];
        })->toArray();

        // Set default payment method if no payments exist
        if (empty($this->payments)) {
            $this->payment_method_name = strtolower(Account::find($this->default_payment_method_id)->name);
            $this->payment = [
                'payment_method_id' => $this->default_payment_method_id,
                'payment_method_name' => $this->payment_method_name,
                'amount' => $this->sales['balance'],
                'name' => null,
            ];
        }
    }

    protected function processItems($items)
    {
        return $items->mapWithKeys(function ($item) {
            $key = $item['employee_id'].'-'.$item['inventory_id'];

            return [$key => $this->formatItem($item)];
        })->toArray();
    }

    protected function processComboOffers($comboOffers)
    {
        return $comboOffers->map(function ($package) {
            $items = collect($this->items)
                ->filter(fn ($item) => $item['sale_combo_offer_id'] == $package['id'])
                ->map(fn ($item) => array_merge($item, [
                    'combo_offer_price' => $item['unit_price'] - $item['discount'],
                ]))
                ->toArray();

            return [
                'id' => $package['id'],
                'combo_offer_id' => $package['combo_offer_id'],
                'amount' => $package['amount'],
                'combo_offer_name' => $package->comboOffer?->name,
                'items' => $items,
            ];
        })->toArray();
    }

    public function selectItem($id)
    {
        // Use Redis for faster inventory lookup
        $inventory = $this->getCachedData(
            $this->cachePrefix."inventory_{$id}",
            $this->cacheTtlShort,
            fn () => Inventory::with(['product:id,name,mrp'])->find($id)
        );

        if (! $inventory) {
            $this->dispatch('error', ['message' => 'Product not found']);

            return false;
        }

        if (! $this->validateEmployee()) {
            return false;
        }

        $this->addToCart($inventory);

        if (in_array($this->payment_method_name, ['cash', 'card'])) {
            $this->selectPaymentMethod($this->payment_method_name);
        }
    }

    public function addToCart($inventory)
    {
        $key = $this->employee_id.'-'.$inventory->id;
        $product = $inventory->product;

        // Pre-calculate sale type price and discount
        $saleTypePrice = $product->saleTypePrice($this->sales['sale_type']);
        $discount = $product->mrp - $saleTypePrice;

        $single = [
            'key' => $key,
            'inventory_id' => $inventory->id,
            'barcode' => $inventory->barcode,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee->name,
            'assistant_name' => '',
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price' => $discount > 0 ? $product->mrp : $saleTypePrice,
            'discount' => $discount > 0 ? $discount : 0,
            'quantity' => 1,
            'tax' => 0,
        ];

        if (isset($this->items[$key])) {
            $this->items[$key]['quantity'] += 1;
        } else {
            $this->items[$key] = $single;
        }

        // Calculate only for this item
        $this->singleCartCalculator($key);
        $this->mainCalculator();
    }

    public function resetItemsBasedOnType()
    {
        foreach ($this->items as $key => $item) {
            $product = Product::find($item['product_id']);

            $saleTypePrice = $product->saleTypePrice($this->sales['sale_type']);
            $discount = $product->mrp - $saleTypePrice;

            $this->items[$key]['discount'] = 0;
            if ($discount > 0) {
                $this->items[$key]['unit_price'] = $product->mrp;
                $this->items[$key]['discount'] = $discount;
            } else {
                $this->items[$key]['unit_price'] = $saleTypePrice;
            }

            $this->singleCartCalculator($key);
        }
        $this->mainCalculator();
    }

    protected function validateEmployee()
    {
        if (! $this->employee_id) {
            $this->dispatch('error', ['message' => 'please select your employee first']);
            $this->dispatch('OpenEmployeeDropBox');

            return false;
        }

        $this->employee = $this->getCachedData(
            $this->cachePrefix."employee_{$this->employee_id}",
            $this->cacheTtlShort,
            fn () => User::find($this->employee_id)
        );

        if (! $this->employee) {
            $this->dispatch('error', ['message' => 'Employee not found']);

            return false;
        }

        $this->employee_id = $this->employee->id;

        return true;
    }

    public function mainCalculator()
    {
        if (empty($this->items)) {
            $this->resetSalesTotals();

            return;
        }

        // Use computed property to avoid recalculating if items haven't changed
        // if ($this->computedItems === $this->items) {
        //     return;
        // }

        $this->computedItems = $this->items;
        $items = collect($this->items);
        $payments = collect($this->payments);
        // Calculate totals in a single pass
        $this->computedTotals = $this->calculateTotals($items);
        // Update sales data in one go
        $this->updateSalesData($this->computedTotals, $payments->sum('amount'));
    }

    protected function calculateTotals($items)
    {
        return $items->reduce(function ($carry, $item) {
            $carry['gross_amount'] += $item['gross_amount'];
            $carry['total_quantity'] += $item['quantity'];
            $carry['item_discount'] += $item['discount'];
            $carry['tax_amount'] += $item['tax_amount'];
            $carry['total'] += $item['total'];

            return $carry;
        }, [
            'gross_amount' => 0,
            'total_quantity' => 0,
            'item_discount' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);
    }

    protected function updateSalesData($totals, $paidAmount)
    {
        $data = [
            'gross_amount' => round($totals['gross_amount'], 2),
            'total_quantity' => round($totals['total_quantity'], 2),
            'item_discount' => round($totals['item_discount'], 2),
            'tax_amount' => round($totals['tax_amount'], 2),
            'total' => round($totals['total'], 2),
            'grand_total' => $this->calculateGrandTotal($totals['total']),
            'paid' => round($paidAmount, 2),
        ];
        $data['balance'] = round($data['grand_total'] - $paidAmount, 2);
        $this->sales = array_merge($this->sales, $data);
        $this->payment['amount'] = $this->sales['balance'];
    }

    protected function formatItem($item)
    {
        return [
            'id' => $item['id'],
            'key' => $item['employee_id'].'-'.$item['inventory_id'],
            'employee_id' => $item['employee_id'],
            'assistant_id' => $item['assistant_id'],
            'inventory_id' => $item['inventory_id'],
            'product_id' => $item['product_id'],
            'sale_combo_offer_id' => $item['sale_combo_offer_id'],
            'name' => $item['name'],
            'employee_name' => $item['employee_name'],
            'assistant_name' => $item['assistant_name'],
            'tax_amount' => $item['tax_amount'],
            'unit_price' => $item['unit_price'],
            'quantity' => round($item['quantity'], 3),
            'gross_amount' => $item['gross_amount'],
            'discount' => $item['discount'],
            'tax' => $item['tax'],
            'total' => $item['total'],
            'effective_total' => $item['effective_total'],
            'created_by' => $item['created_by'],
        ];
    }

    public function getCategories()
    {
        // Cache categories for 1 hour
        $this->categories = Cache::remember('categories_with_products', 3600, function () {
            return Category::withCount('products')
                ->having('products_count', '>', 0)
                ->when($this->category_key, function ($query, $value) {
                    return $query->where('name', 'LIKE', '%'.$value.'%');
                })
                ->orderBy('name')
                ->get()
                ->toArray();
        });
        $this->categoryCount = count($this->categories);
    }

    public function updated($key, $value)
    {
        if (preg_match('/^items\..*/', $key)) {
            $indexes = explode('.', $key);
            $index = $indexes[1] ?? null;
            if (! is_numeric($value)) {
                $this->items[$index][$indexes[2]] = 0;
            }
            $this->cartCalculator($index);
            $this->mainCalculator();
        }
        if (in_array($key, ['sales.other_discount'])) {
            if (str_ends_with($value, '%')) {
                $percentage = rtrim($value, '%');
                $value = round($this->sales['total'] / 100 * $percentage, 2);
                if ($value > $this->sales['total']) {
                    $value = $percentage;
                }
                $this->sales['other_discount'] = $value;
            }
            if (! is_numeric($value)) {
                $this->sales['other_discount'] = 0;
            }
            $this->cartCalculator();
            $this->mainCalculator();
            if (in_array($this->payment_method_name, ['cash', 'card'])) {
                $this->selectPaymentMethod($this->payment_method_name);
            }
        }
        if (in_array($key, ['sales.freight'])) {
            if (! is_numeric($value)) {
                $this->sales['freight'] = 0;
            }
            $this->mainCalculator();
        }
        if ($key == 'barcode_key') {
            $this->getProductByBarcode($value);
            $this->barcode_key = '';
        }
        if ($key == 'sales.sale_type') {
            $this->resetItemsBasedOnType();
            $this->dispatch('Sale-getProducts-Component', $this->sales['sale_type'], $this->category_id, $this->product_key);
        }
        if ($key == 'sales.account_id') {
            $this->getCustomerDetails();
        }
        if (in_array($key, ['product_key'])) {
            $this->dispatch('Sale-getProducts-Component', $this->sales['sale_type'], $this->category_id, $this->product_key);
        }
    }

    public function updateComboOfferItemPrice($items, $comboOffers)
    {
        $this->items = $items;
        $this->comboOffers = $comboOffers;

        $this->cartCalculator();
        $this->mainCalculator();
        if (in_array($this->payment_method_name, ['cash', 'card'])) {
            $this->selectPaymentMethod($this->payment_method_name);
        }
    }

    public function openFeedback()
    {
        $this->sales['rating'] = $this->sales['rating'] ?? 0;
        $this->dispatch('Open-Sale-Feedback-Component', $this->sales);
    }

    public function saveFeedback($sales)
    {
        $this->sales = $sales;
    }

    public function getCustomerDetails()
    {
        // Cache account balance for 5 minutes
        $this->account_balance = Cache::remember(
            "account_balance_{$this->sales['account_id']}",
            300,
            function () {
                $account = Account::find($this->sales['account_id']);
                if (! $account) {
                    return;
                }

                return $account->ledger()
                    ->latest('id')
                    ->value('balance');
            }
        );
    }

    public function updatedInventoryId()
    {
        $inventory = Inventory::find($this->inventory_id);
        $this->employee = User::find($this->employee_id);
        if (! $this->employee) {
            $this->dispatch('error', ['message' => 'Please select any Employee']);

            return false;
        }
        if ($inventory) {
            $this->addToCart($inventory);
            $this->cartCalculator($this->employee_id.'-'.$inventory->id);
            $this->dispatch('OpenProductBox');
        }
    }

    public function modifyQuantity($key, $action)
    {
        if ($action == 'plus') {
            $this->items[$key]['quantity'] += 1;
        } else {
            if ($this->items[$key]['quantity'] > 1) {
                $this->items[$key]['quantity'] -= 1;
            } else {
                $this->dispatch('error', ['message' => "Can't remove quantity any further"]);
            }
        }
        $this->singleCartCalculator($key);
        $this->mainCalculator();
    }

    public function getProductByBarcode($value)
    {
        $inventory = Inventory::firstWhere('barcode', $value);
        if (! $inventory) {
            // $this->dispatch('error', ['message' => 'No Match Found']);

            return false;
        }
        $this->selectItem($inventory->id);
    }

    public function categorySelect($id)
    {
        $this->category_id = $id;
        $this->dispatch('Sale-getProducts-Component', $this->sales['sale_type'], $this->category_id, $this->product_key);
    }

    public function cartCalculator($key = null)
    {
        if ($key) {
            $this->singleCartCalculator($key);
        } else {
            foreach ($this->items as $value) {
                $key = $value['employee_id'].'-'.$value['inventory_id'];
                $this->singleCartCalculator($key);
            }
        }
    }

    public function singleCartCalculator($key)
    {
        if (! isset($this->items[$key])) {
            return;
        }

        $item = &$this->items[$key];

        // Pre-calculate values to avoid multiple calculations
        $gross_amount = $item['unit_price'] * $item['quantity'];
        $net_amount = $gross_amount - $item['discount'];
        $tax_amount = $net_amount * ($item['tax'] / 100);
        $total = round($net_amount + $tax_amount, 2);

        // Update item values in one go
        $item = array_merge($item, [
            'gross_amount' => round($gross_amount, 2),
            'net_amount' => round($net_amount, 2),
            'tax_amount' => round($tax_amount, 2),
            'total' => $total,
            'effective_total' => $this->calculateEffectiveTotal($total),
        ]);
    }

    protected function calculateEffectiveTotal($total)
    {
        if ($this->sales['other_discount'] && $this->sales['total']) {
            $discount_percentage = ($this->sales['other_discount'] / $this->sales['total']) * 100;

            return round($total - ($discount_percentage * $total) / 100, 3);
        }

        return $total;
    }

    protected function calculateGrandTotal($total)
    {
        $grandTotal = $total;
        $grandTotal -= $this->sales['other_discount'];
        $grandTotal += $this->sales['freight'];

        return round($grandTotal, 2);
    }

    protected function resetSalesTotals()
    {
        $this->sales = array_merge($this->sales, [
            'gross_amount' => 0,
            'total_quantity' => 0,
            'item_discount' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'grand_total' => 0,
            'paid' => 0,
            'balance' => 0,
        ]);
        $this->payment['amount'] = 0;
    }

    public function removeSyncItemFromViewItem($index)
    {
        unset($this->items[$index]);
        $this->mainCalculator();
    }

    public function removeItem($index)
    {
        try {
            $id = $this->items[$index]['id'] ?? '';
            if ($id) {
                $response = (new ItemDeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }
            unset($this->items[$index]);
            $this->mainCalculator();
            $this->dispatch('success', ['message' => 'item removed successfully']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function deleteAllItems()
    {
        try {
            foreach ($this->items as $value) {
                $id = $value['id'] ?? '';
                if ($id) {
                    $response = (new ItemDeleteAction())->execute($id);
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                }
            }
            $this->items = [];
            $this->mainCalculator();
            $this->dispatch('success', ['message' => 'items removed successfully']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function removePayment($index)
    {
        try {
            $id = $this->payments[$index]['id'] ?? '';
            if ($id) {
                $response = (new PaymentDeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
            }
            unset($this->payments[$index]);
            $this->mainCalculator();
            $this->dispatch('success', ['message' => 'Payment removed successfully']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function viewItems()
    {
        $this->dispatch('Sale-View-Items-Component', $this->sales['status'], $this->items);
    }

    public function manageComboOffer()
    {
        $this->dispatch('Open-Sale-ComboOffer-Component', $this->items, $this->comboOffers);
    }

    public function editItem($index)
    {
        $this->dispatch('Sale-Edit-Item-Component', $index, $this->items[$index]);
    }

    public function editedItem($id, $item)
    {
        $oldId = $id;
        $newId = $item['employee_id'].'-'.implode('-', array_slice(explode('-', $id), 1));
        if ($newId != $oldId) {
            unset($this->items[$oldId]);
            $item['employee_name'] = User::find($item['employee_id'])->name;
            $item['key'] = $newId;
            $id = $newId;
        }
        $item['assistant_name'] = User::find($item['assistant_id'])?->name;
        $this->items[$id] = $item;
        $this->mainCalculator();
    }

    public function editedItems($items)
    {
        $this->items = $items;
        $this->mainCalculator();
    }

    public function selectPaymentMethod($method)
    {
        $this->payment_method_name = $method;
        if ($method == 'custom') {
            $this->dispatch('Sale-Custom-Payment-Modify', $this->sales, $this->payments);

            return false;
        }
        $account = Account::firstWhere('name', $method);
        if (! $account) {
            $this->dispatch('error', ['message' => 'The selected method has not been assigned to an account head']);

            return false;
        }
        $this->payment['payment_method_id'] = $account->id;

        if ($this->table_id) {
            $this->sale->payments()->delete();
        }
        $this->payments = [];
        $single = [
            'amount' => $this->sales['grand_total'],
            'payment_method_id' => $this->payment['payment_method_id'],
            'name' => $account->name,
        ];
        $this->payments[] = $single;
        $this->mainCalculator();
    }

    public function collectPayments($sales, $payments)
    {
        $this->payments = $payments;
        $this->sales = $sales;
    }

    public function addPayment()
    {
        if (! $this->payment['amount']) {
            $this->dispatch('error', ['message' => 'Please select any amount']);

            return false;
        }
        if (! $this->payment['payment_method_id']) {
            $this->dispatch('error', ['message' => 'Please select any payment method to add']);

            return false;
        }
        if ($this->payment['amount'] > $this->sales['balance']) {
            $this->dispatch('error', ['message' => "You can't pay more than the net total amount"]);

            return false;
        }

        $account = Account::find($this->payment['payment_method_id']);
        $single = [
            'amount' => $this->payment['amount'],
            'payment_method_id' => $this->payment['payment_method_id'],
            'name' => $account->name,
        ];
        $this->payments[] = $single;

        $this->payment['amount'] = 0;
        $this->mainCalculator();
    }

    protected $rules = [
        'sales.account_id' => ['required'],
        'sales.date' => ['required'],
        'sales.sale_type' => ['required'],
    ];

    protected $messages = [
        'sales.account_id' => 'The customer field is required',
        'sales.date' => 'The date field is required',
    ];

    public function submit()
    {
        if (! $this->sales['total_quantity']) {
            $this->dispatch('error', ['message' => 'You need to add at least one product to save the sale!']);

            return false;
        }
        $payment_methods = collect($this->payments)->pluck('name')->toArray();
        $payment_methods = implode(',', $payment_methods);
        if ($this->sales['account_id'] == 3) {
            $customer = $this->sales['customer_name'].'@'.$this->sales['customer_mobile'];
        } else {
            $account = Account::find($this->sales['account_id']);
            $customer = $account->name.'@'.$account->mobile;
        }
        $data = [
            'customer' => $customer,
            'grand_total' => ($this->sales['grand_total']),
            'paid' => ($this->sales['paid']),
            'payment_methods' => $payment_methods,
            'balance' => ($this->sales['balance']),
        ];
        $this->dispatch('show-confirmation', $data);
    }

    public function renderConfirmationDialog($customer, $grandTotal, $paid, $balance, $paymentMethods = null)
    {
        $data = [
            'customer' => $customer,
            'grandTotal' => floatval($grandTotal),
            'paid' => floatval($paid),
            'balance' => floatval($balance),
            'paymentMethods' => $paymentMethods,
        ];

        return view('components.sale.confirmation-dialog', $data)->render();
    }

    public function save($type = 'completed', $print = true)
    {
        $this->validate();
        try {
            $oldStatus = $this->sales['status'];
            DB::beginTransaction();
            if (! count($this->items)) {
                throw new Exception('Please add any item', 1);
            }
            $this->sales['status'] = $type;
            $this->sales['items'] = $this->items;
            $this->sales['payments'] = $this->payments;
            $this->sales['comboOffers'] = $this->comboOffers;
            if ($this->sales['balance'] < 0) {
                throw new Exception('Please check the payment', 1);
            }
            $user_id = Auth::id();
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->sales, $user_id);
            } else {
                $response = (new UpdateAction())->execute($this->sales, $this->table_id, $user_id);
            }
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $table_id = $response['data']['id'];
            $this->mount($this->table_id);
            DB::commit();
            if ($this->send_to_whatsapp) {
                $this->sendToWhatsapp($table_id);
            }
            if ($type == 'completed') {
                $this->dispatch('print-invoice', ['link' => route('print::sale::invoice', $response['data']['id']), 'print' => $print]);
            }
            $this->dispatch('ResetSelectBox');
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
            $this->sales['status'] = $oldStatus;
        }
    }

    public function sendToWhatsapp($table_id = null)
    {
        if (! $table_id) {
            $table_id = $this->table_id;
        }
        $sale = Sale::find($table_id);
        if ($sale['customer_mobile']) {
            $number = $sale['customer_mobile'];
        } else {
            $number = $sale->account->mobile;
        }
        $imageContent = SaleHelper::saleInvoice($table_id, 'thermal');
        $image_path = SaleHelper::convertHtmlToImage($imageContent, $sale->invoice_no);
        if (! $number) {
            $this->dispatch('error', ['message' => 'Invalid Number']);

            goto skip;
        }
        $data = [
            'number' => $number,
            'message' => 'Please Check Your Invoice : '.currency($sale->grand_total),
            'filePath' => $image_path,
        ];
        $response = WhatsappHelper::send($data);
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);
        } else {
            $this->dispatch('success', ['message' => $response['message']]);
        }
        skip :
    }

    public function render()
    {
        switch (cache('sale_type')) {
            case 'pos':
                return view('livewire.sale.pos');
            case 'version_1':
                return view('livewire.sale.page');
            case 'version_2':
                return view('livewire.sale.page-version-two');
            default:
                return view('livewire.sale.page');
        }
    }
}
