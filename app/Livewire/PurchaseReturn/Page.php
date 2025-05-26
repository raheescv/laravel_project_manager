<?php

namespace App\Livewire\PurchaseReturn;

use App\Actions\PurchaseReturn\CreateAction;
use App\Actions\PurchaseReturn\Item\DeleteAction as ItemDeleteAction;
use App\Actions\PurchaseReturn\Payment\DeleteAction as PaymentDeleteAction;
use App\Actions\PurchaseReturn\UpdateAction;
use App\Models\Account;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public $product_id;

    public $table_id;

    public $account_balance;

    public $purchase_return;

    public $purchase_returns = [];

    public $accounts = [];

    public $items = [];

    public $payment = [];

    public $payments = [];

    public $paymentMethods = [];

    public $payment_method_name;

    public $default_payment_method_id = 1;

    protected $listeners = [
        'PurchaseReturn-SelectProduct' => 'selectProduct',
    ];

    protected $rules = [
        'purchase_returns.account_id' => ['required'],
        'purchase_returns.date' => ['required'],
        'purchase_returns.invoice_no' => ['required'],
    ];

    protected $messages = [
        'purchase_returns.account_id' => 'The vendor is required',
        'purchase_returns.date' => 'The date field is required',
        'purchase_returns.invoice_no' => 'The invoice no field is required',
    ];

    public function mount($id = null)
    {
        $this->table_id = $id;
        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();

        $this->payment_method_name = '';
        $this->payment = [
            'payment_method_id' => $this->default_payment_method_id,
            'payment_method_name' => '',
            'amount' => 0,
            'name' => null,
        ];

        if ($this->table_id) {
            $this->purchase_return = PurchaseReturn::with('account:id,name', 'branch:id,name', 'items.product:id,name', 'items.purchaseItem.purchase:id,invoice_no', 'createdUser:id,name', 'updatedUser:id,name', 'cancelledUser:id,name', 'payments.paymentMethod:id,name')->find($this->table_id);
            if (! $this->purchase_return) {
                return redirect()->route('purchase_return::index');
            }
            $this->purchase_returns = $this->purchase_return->toArray();
            $this->accounts = [];
            $this->items = $this->purchase_return->items->mapWithKeys(function ($item) {
                $key = $item['product_id'];

                return [
                    $key => [
                        'id' => $item['id'],
                        'key' => $key,
                        'purchase_invoice_no' => $item->purchaseItem?->purchase?->invoice_no,
                        'purchase_item_id' => $item['purchase_item_id'],
                        'product_id' => $item['product_id'],
                        'name' => $item['name'],
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

            $this->payments = $this->purchase_return->payments->map->only(['id', 'amount', 'date', 'payment_method_id', 'created_by', 'name'])->toArray();
            $this->mainCalculator();

        } else {
            $this->items = [];
            $this->payments = [];
            $this->purchase_returns = [
                'date' => date('Y-m-d'),
                'invoice_no' => '',
                'branch_id' => session('branch_id'),
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
        $this->dispatch('SelectDropDownValues', $this->purchase_returns);
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
        if (in_array($key, ['purchase_returns.other_discount'])) {
            if (str_ends_with($value, '%')) {
                $percentage = rtrim($value, '%');
                $value = round($this->purchase_returns['total'] / 100 * $percentage, 2);
                if ($value > $this->purchase_returns['total']) {
                    $value = $percentage;
                }
                $this->purchase_returns['other_discount'] = $value;
            }
            if (! is_numeric($value)) {
                $this->purchase_returns['other_discount'] = 0;
            }
            $this->mainCalculator();
        }
        if (in_array($key, ['purchase_returns.freight'])) {
            if (! is_numeric($value)) {
                $this->purchase_returns['freight'] = 0;
            }
            $this->mainCalculator();
        }
        if ($key == 'purchase_returns.account_id') {
            $this->getAccountDetails();
        }
    }

    public function getAccountDetails()
    {
        $account = Account::find($this->purchase_returns['account_id']);
        if ($account) {
            $this->account_balance = $account->ledger()->latest('id')->value('balance');
        }
    }

    public function selectProduct($product_id, $purchase_item_id = null)
    {
        $product = Product::find($product_id);
        $purchaseItem = PurchaseItem::find($purchase_item_id);
        if ($product) {
            $this->addToCart($product, $purchaseItem);
            $this->cartCalculator($product->id);
            $this->dispatch('OpenProductBox');
        }
    }

    public function cartCalculator($key = null)
    {
        if ($key) {
            $this->singleCartCalculator($key);
        } else {
            foreach ($this->items as $key => $value) {
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
        $this->items[$key]['total'] = round($net_amount + $tax_amount, 2);
    }

    public function mainCalculator()
    {
        $items = collect($this->items);
        $payments = collect($this->payments);

        $this->purchase_returns['gross_amount'] = round($items->sum('gross_amount'), 2);
        $this->purchase_returns['total_quantity'] = round($items->sum('quantity'), 2);
        $this->purchase_returns['item_discount'] = round($items->sum('discount'), 2);
        $this->purchase_returns['tax_amount'] = round($items->sum('tax_amount'), 2);

        $this->purchase_returns['total'] = round($items->sum('total'), 2);

        $this->purchase_returns['grand_total'] = $this->purchase_returns['total'];
        $this->purchase_returns['grand_total'] -= $this->purchase_returns['other_discount'];
        $this->purchase_returns['grand_total'] += $this->purchase_returns['freight'];
        $this->purchase_returns['grand_total'] = round($this->purchase_returns['grand_total'], 2);

        $this->purchase_returns['paid'] = round($payments->sum('amount'), 2);
        $this->purchase_returns['balance'] = round($this->purchase_returns['grand_total'] - $this->purchase_returns['paid'], 2);
        $this->payment['amount'] = round($this->purchase_returns['balance'], 2);
    }

    public function addToCart($product, $purchaseItem = null)
    {
        $key = $product->id;
        $single = [
            'key' => $key,
            'purchase_item_id' => '',
            'purchase_invoice_no' => '',
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price' => $product->cost,
            'discount' => 0,
            'quantity' => 1,
            'tax' => 0,
        ];
        if ($purchaseItem) {
            $single['purchase_item_id'] = $purchaseItem['id'];
            $single['purchase_invoice_no'] = $purchaseItem->purchase?->invoice_no;
            $single['unit_price'] = $purchaseItem['unit_price'];
            $single['discount'] = $purchaseItem['discount'];
            $single['quantity'] = $purchaseItem['quantity'];
            $single['tax'] = $purchaseItem['tax'];
        }
        if (isset($this->items[$key])) {
            $this->items[$key]['quantity'] += 1;
        } else {
            $this->items[$key] = $single;
        }
        $this->singleCartCalculator($key);
        $this->mainCalculator();
    }

    public function removeItem($index)
    {
        try {
            $id = $this->items[$index]['id'] ?? '';
            if ($id) {
                $response = (new ItemDeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            unset($this->items[$index]);
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
        if ($this->payment['amount'] > $this->purchase_returns['balance']) {
            $this->dispatch('error', ['message' => "You can't pay more than the net total amount"]);

            return false;
        }

        $account = Account::find($this->payment['payment_method_id']);
        if (! $account) {
            $this->dispatch('error', ['message' => 'Please select the payment method']);

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

    public function submit()
    {
        if (! $this->purchase_returns['total_quantity']) {
            $this->dispatch('error', ['message' => 'You need to add at least one product to save the purchase return!']);

            return false;
        }
        $payment_methods = collect($this->payments)->pluck('name')->toArray();
        $payment_methods = implode(',', $payment_methods);
        $account = Account::find($this->purchase_returns['account_id']);
        if (! $account) {
            $this->dispatch('error', ['message' => 'Please select the vendor']);

            return false;
        }
        $vendor = $account->name.'@'.$account->mobile;
        $this->dispatch('show-confirmation', [
            'vendor' => $vendor,
            'grand_total' => currency($this->purchase_returns['grand_total']),
            'paid' => currency($this->purchase_returns['paid']),
            'payment_methods' => $payment_methods,
            'balance' => currency($this->purchase_returns['balance']),
        ]);
    }

    public function save($type = 'completed')
    {
        $this->validate();
        try {
            $account_id = $this->purchase_returns['account_id'];
            $oldStatus = $this->purchase_returns['status'];
            DB::beginTransaction();
            if (! count($this->items)) {
                throw new \Exception('Please add any item', 1);
            }
            $this->purchase_returns['status'] = $type;
            $this->purchase_returns['items'] = $this->items;
            $this->purchase_returns['payments'] = $this->payments;
            if ($this->purchase_returns['balance'] < 0) {
                throw new \Exception('Please check the payment', 1);
            }
            $user_id = Auth::id();
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->purchase_returns, $user_id);
            } else {
                $response = (new UpdateAction())->execute($this->purchase_returns, $this->table_id, $user_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->mount($this->table_id);
            $this->purchase_returns['account_id'] = $account_id;
            DB::commit();
            $this->dispatch('ResetSelectBox', ['type' => $type]);
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
            $this->purchase_returns['status'] = $oldStatus;
        }
    }

    public function render()
    {
        return view('livewire.purchase-return.page');
    }
}
