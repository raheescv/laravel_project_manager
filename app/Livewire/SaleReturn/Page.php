<?php

namespace App\Livewire\SaleReturn;

use App\Actions\SaleReturn\CreateAction;
use App\Actions\SaleReturn\Item\DeleteAction as ItemDeleteAction;
use App\Actions\SaleReturn\Payment\DeleteAction as PaymentDeleteAction;
use App\Actions\SaleReturn\UpdateAction;
use App\Models\Account;
use App\Models\Configuration;
use App\Models\Inventory;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'SaleReturn-Custom-Payment-Confirmed' => 'collectPayments',
        'SaleReturn-Edited-Items-Component' => 'editedItems',
        'SaleReturn-Edited-Item-Component' => 'editedItem',
        'SaleReturn-selectItem-Component' => 'selectItem',
        'SaleReturn-Delete-Sync-Items-Component' => 'removeSyncItemFromViewItem',
    ];

    public $product_key;

    public $products;

    public $table_id;

    public $accounts;

    public $sale_id;

    public $items = [];

    public $payment = [];

    public $payments = [];

    public $paymentMethods = [];

    public $payment_method_name;

    public $sale_return;

    public $sale_returns = [];

    public $default_payment_method_id = 1;

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();
        $this->default_payment_method_id = Configuration::where('key', 'default_payment_method_id')->value('value') ?? 1;
        $this->payment_method_name = strtolower(Account::find($this->default_payment_method_id)->name);
        $this->payment = [
            'payment_method_id' => $this->default_payment_method_id,
            'payment_method_name' => $this->payment_method_name,
            'amount' => 0,
            'name' => null,
        ];

        if ($this->table_id) {
            $this->sale_return = SaleReturn::with('account:id,name', 'branch:id,name', 'items.product:id,name', 'createdUser:id,name', 'updatedUser:id,name', 'payments.paymentMethod:id,name')->find($this->table_id);
            if (! $this->sale_return) {
                return redirect()->route('sale_return::index');
            }
            $this->sale_returns = $this->sale_return->toArray();
            $this->accounts = Account::where('id', $this->sale_returns['account_id'])->pluck('name', 'id')->toArray();
            $this->items = $this->sale_return->items->mapWithKeys(function ($item) {
                $key = $item['inventory_id'];

                return [
                    $key => [
                        'id' => $item['id'],
                        'key' => $key,
                        'inventory_id' => $item['inventory_id'],
                        'product_id' => $item['product_id'],
                        'name' => $item['name'],
                        'tax_amount' => $item['tax_amount'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => round($item['quantity'], 3),
                        'gross_amount' => $item['gross_amount'],
                        'discount' => $item['discount'],
                        'tax' => $item['tax'],
                        'total' => $item['total'],
                        'effective_total' => $item['effective_total'],
                        'created_by' => $item['created_by'],
                    ],
                ];
            })->toArray();
            $this->payments = $this->sale_return->payments->map->only(['id', 'amount', 'date', 'payment_method_id', 'created_by', 'name'])->toArray();
            if (count($this->payments) > 1) {
                $this->payment_method_name = 'custom';
            }
            if (count($this->payments) == 1) {
                $this->payment_method_name = strtolower($this->payments[0]['name']);
                if (! in_array($this->payment_method_name, ['cash', 'card'])) {
                    $this->payment_method_name = 'custom';
                }
            }
            $this->mainCalculator();

        } else {
            $this->accounts = Account::where('id', 3)->pluck('name', 'id')->toArray();
            $this->items = [];
            $this->payments = [];
            $this->sale_returns = [
                'date' => date('Y-m-d'),
                'account_id' => 3,
                'gross_amount' => 0,
                'total_quantity' => 0,
                'item_discount' => 0,
                'tax_amount' => 0,

                'total' => 0,

                'other_discount' => 0,
                'grand_total' => 0,

                'paid' => 0,
                'balance' => 0,
                'status' => 'draft',
            ];
        }
        $this->dispatch('SelectDropDownValues', $this->sale_returns);
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
        if (in_array($key, ['sale_returns.other_discount'])) {
            if (str_ends_with($value, '%')) {
                $percentage = rtrim($value, '%');
                $value = round($this->sale_returns['total'] / 100 * $percentage, 2);
                if ($value > $this->sale_returns['total']) {
                    $value = $percentage;
                }
                $this->sale_returns['other_discount'] = $value;
            }
            if (! is_numeric($value)) {
                $this->sale_returns['other_discount'] = 0;
            }
            $this->cartCalculator();
            $this->mainCalculator();
            if (in_array($this->payment_method_name, ['cash', 'card'])) {
                $this->selectPaymentMethod($this->payment_method_name);
            }
        }
        if (in_array($key, ['product_key', 'sale_id'])) {
            $this->dispatch('SaleReturn-getProducts-Component', $this->sale_id, $this->product_key);
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

    public function cartCalculator($key = null)
    {
        if ($key) {
            $this->singleCartCalculator($key);
        } else {
            foreach ($this->items as $value) {
                $key = $value['inventory_id'];
                $this->singleCartCalculator($key);
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
        $total = round($net_amount + $tax_amount, 2);
        $this->items[$key]['total'] = $total;
        if ($this->sale_returns['other_discount'] && $this->sale_returns['total']) {
            $discount_percentage = ($this->sale_returns['other_discount'] / $this->sale_returns['total']) * 100;
            $this->items[$key]['effective_total'] = round($total - ($discount_percentage * $total) / 100, 3);
        } else {
            $this->items[$key]['effective_total'] = $total;
        }
    }

    public function mainCalculator()
    {
        $items = collect($this->items);
        $payments = collect($this->payments);
        $this->sale_returns['gross_amount'] = round($items->sum('gross_amount'), 2);
        $this->sale_returns['total_quantity'] = round($items->sum('quantity'), 2);
        $this->sale_returns['item_discount'] = round($items->sum('discount'), 2);
        $this->sale_returns['tax_amount'] = round($items->sum('tax_amount'), 2);

        $this->sale_returns['total'] = round($items->sum('total'), 2);

        $this->sale_returns['grand_total'] = $this->sale_returns['total'];
        $this->sale_returns['grand_total'] -= $this->sale_returns['other_discount'];
        $this->sale_returns['grand_total'] = round($this->sale_returns['grand_total'], 2);

        $this->sale_returns['paid'] = round($payments->sum('amount'), 2);
        $this->sale_returns['balance'] = round($this->sale_returns['grand_total'] - $this->sale_returns['paid'], 2);
        $this->payment['amount'] = round($this->sale_returns['balance'], 2);
    }

    public function selectItem($inventory_id, $sale_item_id = null)
    {
        $inventory = Inventory::find($inventory_id);
        $saleItem = SaleItem::find($sale_item_id);
        $this->addToCart($inventory, $saleItem);
        $this->cartCalculator($inventory_id);
        if (in_array($this->payment_method_name, ['cash', 'card'])) {
            $this->selectPaymentMethod($this->payment_method_name);
        }
    }

    public function addToCart($inventory, $saleItem)
    {
        $inventory_id = $inventory->id;
        $single = [
            'key' => $inventory_id,
            'sale_item_id' => null,
            'inventory_id' => $inventory_id,
            'barcode' => $inventory->barcode,
            'product_id' => $inventory->product_id,
            'name' => $inventory->product?->name,
            'unit_price' => $inventory->product->mrp,
            'discount' => 0,
            'quantity' => 1,
            'tax' => $inventory->product->tax ?? 0,
        ];
        if ($saleItem) {
            $single['sale_item_id'] = $saleItem->id;
            $single['unit_price'] = $saleItem->unit_price;
            $single['discount'] = $saleItem->discount;
            $single['tax'] = $saleItem->tax;
        }

        if (isset($this->items[$inventory_id])) {
            $this->items[$inventory_id]['quantity'] += 1;
        } else {
            $this->items[$inventory_id] = $single;
        }
        $this->singleCartCalculator($inventory_id);
        $this->mainCalculator();
        // $this->dispatch('success', ['message' => 'item added successfully']);
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

    public function viewItems()
    {
        $this->dispatch('SaleReturn-View-Items-Component', $this->sale_returns['status'], $this->items);
    }

    public function editItem($index)
    {
        $this->dispatch('SaleReturn-Edit-Item-Component', $index, $this->items[$index]);
    }

    public function editedItem($id, $item)
    {
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
            $this->dispatch('SaleReturn-Custom-Payment-Modify', $this->sale_returns, $this->payments);

            return false;
        }
        $account = Account::firstWhere('name', $method);
        if (! $account) {
            $this->dispatch('error', ['message' => 'The selected method has not been assigned to an account head']);

            return false;
        }
        $this->payment['payment_method_id'] = $account->id;

        if ($this->table_id) {
            $this->sale_return->payments()->delete();
        }
        $this->payments = [];
        $single = [
            'amount' => $this->sale_returns['grand_total'],
            'payment_method_id' => $this->payment['payment_method_id'],
            'name' => $account->name,
        ];
        $this->payments[] = $single;
        $this->mainCalculator();
    }

    public function collectPayments($sale_returns, $payments)
    {
        $this->payments = $payments;
        $this->sale_returns = $sale_returns;
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
        if ($this->payment['amount'] > $this->sale_returns['balance']) {
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

    protected $rules = [
        'sale_returns.account_id' => ['required'],
        'sale_returns.date' => ['required'],
    ];

    protected $messages = [
        'sale_returns.account_id' => 'The customer field is required',
        'sale_returns.date' => 'The date field is required',
    ];

    public function submit()
    {
        if (! $this->sale_returns['total_quantity']) {
            $this->dispatch('error', ['message' => 'You need to add at least one product to save the sale!']);

            return false;
        }
        $payment_methods = collect($this->payments)->pluck('name')->toArray();
        $payment_methods = implode(',', $payment_methods);
        $account = Account::find($this->sale_returns['account_id']);
        $customer = $account->name.'@'.$account->mobile;
        $this->dispatch('show-confirmation', [
            'customer' => $customer,
            'grand_total' => currency($this->sale_returns['grand_total']),
            'paid' => currency($this->sale_returns['paid']),
            'payment_methods' => $payment_methods,
            'balance' => currency($this->sale_returns['balance']),
        ]);
    }

    public function save($type = 'completed')
    {
        $this->validate();
        try {
            $oldStatus = $this->sale_returns['status'];
            DB::beginTransaction();
            if (! count($this->items)) {
                throw new Exception('Please add any item', 1);
            }
            $this->sale_returns['status'] = $type;
            $this->sale_returns['items'] = $this->items;
            $this->sale_returns['payments'] = $this->payments;
            if ($this->sale_returns['balance'] < 0) {
                throw new Exception('Please check the payment', 1);
            }
            $user_id = Auth::id();
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->sale_returns, $user_id);
            } else {
                $response = (new UpdateAction())->execute($this->sale_returns, $this->table_id, $user_id);
            }
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $this->mount($this->table_id);
            DB::commit();
            $this->dispatch('ResetSelectBox');
            $this->dispatch('success', ['message' => $response['message']]);
            if ($oldStatus == 'completed') {
                return redirect(route('sale_return::view', $this->table_id));
            }
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
            $this->sale_returns['status'] = $oldStatus;
        }
    }

    public function render()
    {
        return view('livewire.sale-return.page');
    }
}
