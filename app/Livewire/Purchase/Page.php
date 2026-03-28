<?php

namespace App\Livewire\Purchase;

use App\Actions\Purchase\CreateAction;
use App\Actions\Purchase\Item\DeleteAction as ItemDeleteAction;
use App\Actions\Purchase\Payment\DeleteAction as PaymentDeleteAction;
use App\Actions\Purchase\UpdateAction;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Page extends Component
{
    public $product_id;

    public $barcode_key;

    public $table_id;

    public $account_balance;

    public $accounts;

    public $items = [];

    public $payment = [];

    public $payments = [];

    public $paymentMethods = [];

    public $payment_method_name;

    public $purchase;

    public $purchases = [];

    public $default_payment_method_id = 1;

    public $purchase_item_row_mode = 'merge';

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        $this->purchase_item_row_mode = Configuration::where('key', 'purchase_item_row_mode')->value('value') ?? 'merge';
        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();

        $this->payment_method_name = '';
        $this->payment = [
            'payment_method_id' => $this->default_payment_method_id,
            'payment_method_name' => '',
            'amount' => 0,
            'name' => null,
        ];

        if ($this->table_id) {
            $this->purchase = Purchase::with([
                'account:id,name,mobile',
                'branch:id,name',
                'items.product.unit:id,name,code',
                'items.product.units.subUnit:id,name,code',
                'items.product:id,name,barcode,unit_id,cost',
                'createdUser:id,name',
                'updatedUser:id,name',
                'cancelledUser:id,name',
                'payments.paymentMethod:id,name',
            ])->find($this->table_id);
            if (! $this->purchase) {
                return redirect()->route('purchase::index');
            }
            $this->purchases = $this->purchase->toArray();
            $this->accounts = [];
            $this->items = $this->purchase->items->mapWithKeys(function ($item) {
                $key = $this->buildItemKey($item['product_id'], $item['id'] ?? null);

                return [
                    $key => [
                        'id' => $item['id'],
                        'key' => $key,
                        'product_id' => $item['product_id'],
                        'name' => $item['name'],
                        'barcode' => $item->product?->barcode,
                        'unit_id' => $item['unit_id'] ?: $item->product?->unit_id,
                        'conversion_factor' => $item['conversion_factor'] ?: 1,
                        'units' => $this->getProductUnits($item->product),
                        'tax_amount' => $item['tax_amount'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => round($item['quantity'], 3),
                        'gross_amount' => $item['gross_amount'],
                        'discount' => $item['discount'],
                        'tax' => $item['tax'],
                        'total' => $item['total'],
                        'created_by' => $item['created_by'],
                    ],
                ];
            })->toArray();

            $this->payments = $this->purchase->payments->map->only(['id', 'amount', 'date', 'payment_method_id', 'created_by', 'name'])->toArray();
            $this->mainCalculator();

        } else {
            $this->accounts = [];
            $this->items = [];
            $this->payments = [];
            $this->purchases = [
                'date' => date('Y-m-d'),
                'delivery_date' => date('Y-m-d'),
                'account_id' => '',

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

                'address' => null,
                'status' => 'draft',
            ];
        }
        $this->getAccountDetails();
        $this->dispatch('SelectDropDownValues', $this->purchases);
    }

    public function updated($key, $value)
    {
        if (preg_match('/^items\.(.*?)\.unit_id$/', $key, $matches)) {
            $index = $matches[1];
            $this->updateUnit($index, $value);
        }
        if (preg_match('/^items\..*/', $key)) {
            $indexes = explode('.', $key);
            $index = $indexes[1] ?? null;
            if (! is_numeric($value)) {
                $this->items[$index][$indexes[2]] = 0;
            }
            $this->cartCalculator($index);
            $this->mainCalculator();
        }
        if (in_array($key, ['purchases.other_discount'])) {
            if (str_ends_with($value, '%')) {
                $percentage = rtrim($value, '%');
                $value = round($this->purchases['total'] / 100 * $percentage, 2);
                if ($value > $this->purchases['total']) {
                    $value = $percentage;
                }
                $this->purchases['other_discount'] = $value;
            }
            if (! is_numeric($value)) {
                $this->purchases['other_discount'] = 0;
            }
            $this->mainCalculator();
        }
        if (in_array($key, ['purchases.freight'])) {
            if (! is_numeric($value)) {
                $this->purchases['freight'] = 0;
            }
            $this->mainCalculator();
        }
        if ($key == 'barcode_key') {
            $this->getProductByBarcode($value);
            $this->barcode_key = '';
        }
        if ($key == 'purchases.account_id') {
            $this->getAccountDetails();
        }
    }

    public function getAccountDetails()
    {
        $account = Account::find($this->purchases['account_id']);
        if ($account) {
            $this->account_balance = $account->ledger()->latest('id')->value('balance');
        }
    }

    public function updatedProductId()
    {
        $product = Product::find($this->product_id);
        if ($product) {
            $key = $this->addToCart($product);
            $this->cartCalculator($key);
            $this->dispatch('OpenProductBox');
        }
    }

    public function getProductByBarcode($value)
    {
        $Product = Product::firstWhere('barcode', $value);
        if (! $Product) {
            // $this->dispatch('error', ['message' => 'No Match Found']);

            return false;
        }
        $this->selectItem($Product->id);
    }

    public function cartCalculator($key = null)
    {
        if ($key) {
            $this->singleCartCalculator($key);
        } else {
            foreach (array_keys($this->items) as $itemKey) {
                $this->singleCartCalculator($itemKey);
            }
        }
    }

    public function singleCartCalculator($key)
    {
        $gross_amount = $this->items[$key]['unit_price'] * $this->items[$key]['quantity'];
        $net_amount = $gross_amount - $this->items[$key]['discount'];
        $tax_amount = $net_amount * $this->items[$key]['tax'] / 100;

        $this->items[$key]['gross_amount'] = round($gross_amount, 2);
        $this->items[$key]['net_amount'] = round($net_amount, 2);
        $this->items[$key]['tax_amount'] = round($tax_amount, 2);
        $this->items[$key]['total'] = round($net_amount + $tax_amount, 2);
    }

    public function mainCalculator()
    {
        $items = collect($this->items);
        $payments = collect($this->payments);
        $this->purchases['gross_amount'] = round($items->sum('gross_amount'), 2);
        $this->purchases['total_quantity'] = round($items->sum('quantity'), 2);
        $this->purchases['item_discount'] = round($items->sum('discount'), 2);
        $this->purchases['tax_amount'] = round($items->sum('tax_amount'), 2);

        $this->purchases['total'] = round($items->sum('total'), 2);

        $this->purchases['grand_total'] = $this->purchases['total'];
        $this->purchases['grand_total'] -= $this->purchases['other_discount'];
        $this->purchases['grand_total'] += $this->purchases['freight'];
        $this->purchases['grand_total'] = round($this->purchases['grand_total'], 2);

        $this->purchases['paid'] = round($payments->sum('amount'), 2);
        $this->purchases['balance'] = round($this->purchases['grand_total'] - $this->purchases['paid'], 2);
        $this->payment['amount'] = round($this->purchases['balance'], 2);
    }

    public function addToCart($product)
    {
        $product->load(['unit', 'units.subUnit']);

        $defaultQuantity = (float) (Configuration::where('key', 'purchase_default_quantity')->value('value') ?? '1');
        $key = $this->findMatchingItemKey((int) $product->id) ?? $this->buildItemKey((int) $product->id);

        $single = [
            'key' => $key,
            'product_id' => $product->id,
            'name' => $product->name,
            'barcode' => $product->barcode,
            'unit_id' => $product->unit_id,
            'conversion_factor' => 1,
            'units' => $this->getProductUnits($product),
            'unit_price' => $product->cost,
            'discount' => 0,
            'quantity' => $defaultQuantity,
            'tax' => 0,
        ];
        if (isset($this->items[$key])) {
            $this->items[$key]['quantity'] += $defaultQuantity;
        } else {
            // Prepend new item so last added appears first
            $this->items = [$key => $single] + $this->items;
        }
        $this->singleCartCalculator($key);
        $this->mainCalculator();

        return $key;
    }

    public function getProductUnits($product)
    {
        return $product->getResolvedUnits();
    }

    public function updateUnit($index, $unit_id)
    {
        $units = $this->items[$index]['units'];
        $selectedUnit = collect($units)->firstWhere('id', $unit_id);
        if ($selectedUnit) {
            $this->items[$index]['conversion_factor'] = $selectedUnit['conversion_factor'];

            // Recalculate Unit Price based on Conversion Factor
            $product = Product::find($this->items[$index]['product_id']);
            if ($product) {
                $this->items[$index]['unit_price'] = round($product->cost * $this->items[$index]['conversion_factor'], 2);
            }
        }
        $this->cartCalculator($index);
        $this->mainCalculator();
    }

    public function removeItem($index)
    {
        $this->skipRender();

        try {
            // Find the item - by array key first, then by 'key' property
            $targetKey = null;
            if (isset($this->items[$index])) {
                $targetKey = $index;
            } else {
                foreach ($this->items as $arrKey => $item) {
                    if (($item['key'] ?? '') == $index || $arrKey == $index) {
                        $targetKey = $arrKey;
                        break;
                    }
                }
            }

            if ($targetKey === null) {
                throw new \Exception('Item not found', 1);
            }

            $id = $this->items[$targetKey]['id'] ?? '';
            if ($id) {
                $response = (new ItemDeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }

            unset($this->items[$targetKey]);
            $this->items = array_values($this->items);
            $this->mainCalculator();
            $this->dispatch('success', ['message' => 'item removed successfully']);
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
                    throw new \Exception($response['message'], 1);
                }
            }
            unset($this->payments[$index]);
            $this->payments = array_values($this->payments);
            $this->mainCalculator();
            $this->dispatch('success', ['message' => 'Payment removed successfully']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
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
        if ($this->payment['amount'] > $this->purchases['balance']) {
            $this->dispatch('error', ['message' => "You can't pay more than the net total amount"]);

            return false;
        }

        $account = Account::find($this->payment['payment_method_id']);
        if (! $account) {
            $this->dispatch('error', ['message' => 'Please select the vendor']);

            return false;
        }
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
        'purchases.account_id' => ['required'],
        'purchases.date' => ['required'],
        'purchases.invoice_no' => ['required'],
    ];

    protected $messages = [
        'purchases.account_id' => 'The vendor is required',
        'purchases.date' => 'The date field is required',
        'purchases.invoice_no' => 'The invoice no field is required',
    ];

    public function submit()
    {
        if (! $this->purchases['total_quantity']) {
            $this->dispatch('error', ['message' => 'You need to add at least one product to save the purchase!']);

            return false;
        }
        $payment_methods = collect($this->payments)->pluck('name')->toArray();
        $payment_methods = implode(',', $payment_methods);
        $account = Account::find($this->purchases['account_id']);
        if (! $account) {
            $this->dispatch('error', ['message' => 'Please select the vendor']);

            return false;
        }
        $vendor = $account->name.'@'.$account->mobile;
        $this->dispatch('show-confirmation', [
            'vendor' => $vendor,
            'invoice_no' => $this->purchases['invoice_no'] ?? 'N/A',
            'grand_total' => currency($this->purchases['grand_total']),
            'paid' => currency($this->purchases['paid']),
            'payment_methods' => $payment_methods,
            'balance' => currency($this->purchases['balance']),
        ]);
    }

    public function save($type = 'completed')
    {
        try {
            $this->validate();
        } catch (ValidationException $e) {
            // Dispatch validation errors to frontend with custom messages
            $errorMessages = [];
            foreach ($e->errors() as $field => $messages) {
                // Use custom messages if available, otherwise use default
                foreach ($messages as $message) {
                    $errorMessages[] = $message;
                }
            }
            $this->dispatch('validation-errors', $errorMessages);

            return;
        }
        try {
            $account_id = $this->purchases['account_id'];
            $oldStatus = $this->purchases['status'];

            DB::beginTransaction();
            if (! count($this->items)) {
                throw new \Exception('Please add any item', 1);
            }
            $this->purchases['status'] = $type;
            $this->purchases['items'] = $this->items;
            $this->purchases['payments'] = $this->payments;

            $user_id = Auth::id();

            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->purchases, $user_id);
            } else {
                $response = (new UpdateAction())->execute($this->purchases, $this->table_id, $user_id);
            }

            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }

            $table_id = $response['data']['id'];

            $this->mount($this->table_id);
            $this->purchases['account_id'] = $account_id;

            DB::commit();

            $this->dispatch('ResetSelectBox', ['type' => $type]);
            $this->dispatch('success', ['message' => $response['message']]);

            // Redirect to barcode print if enabled in configuration
            $enableBarcodePrint = Configuration::where('key', 'enable_barcode_print_after_submit')->value('value') ?? 'no';
            if ($enableBarcodePrint === 'yes') {
                $this->dispatch('redirect-to-print', id: $table_id);
            }

        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
            $this->purchases['status'] = $oldStatus;

        }
    }

    public function render()
    {
        return view('livewire.purchase.page');
    }

    protected function buildItemKey(int $productId, int|string|null $suffix = null): string
    {
        $baseKey = (string) $productId;

        if ($suffix !== null) {
            return $baseKey.'-'.$suffix;
        }

        if ($this->purchase_item_row_mode === 'separate') {
            return $baseKey.'-'.uniqid();
        }

        return $baseKey;
    }

    protected function findMatchingItemKey(int $productId): ?string
    {
        if ($this->purchase_item_row_mode === 'separate') {
            return null;
        }

        foreach ($this->items as $key => $item) {
            if ((int) ($item['product_id'] ?? 0) === $productId) {
                return $key;
            }
        }

        return null;
    }
}
