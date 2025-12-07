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
use App\Models\Sale;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Pager extends Component
{
    protected $listeners = [
        'SaleReturn-Custom-Payment-Confirmed' => 'collectPayments',
        'SaleReturn-Edited-Items-Component' => 'editedItems',
        'SaleReturn-Edited-Item-Component' => 'editedItem',
        'SaleReturn-selectItem-Component' => 'selectItem',
        'SaleReturn-Delete-Sync-Items-Component' => 'removeSyncItemFromViewItem',
    ];



    // new: store derived percentage and original sale total
public $discount_percentage = null;
public $original_total = 0;

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

   public function mount($sale_id = null)
{
    $this->table_id = null;

    $this->default_payment_method_id = Configuration::where('key', 'default_payment_method_id')->value('value') ?? 1;
    $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();
    $this->payment_method_name = strtolower(Account::find($this->default_payment_method_id)->name);

    $this->payment = [
        'payment_method_id' => $this->default_payment_method_id,
        'payment_method_name' => $this->payment_method_name,
        'amount' => 0,
        'name' => null,
    ];

   if ($sale_id) {
    // Fetch the sale
    $sale = Sale::find($sale_id);
    if (!$sale) {
        return redirect()->route('sale_return::index');
    }

    $this->sale_return = $sale;
    $this->table_id = $sale->id;

    // Initialize sale_returns array to prevent undefined keys
    $this->sale_returns = [
        'date' => $sale->date,
        'account_id' => $sale->account_id,
        'gross_amount' => 0,
        'total_quantity' => 0,
        'item_discount' => 0,
        'tax_amount' => 0,
        'total' => 0,
        'other_discount' => $sale->other_discount ?? 0,
        'grand_total' => 0,
        'paid' => 0,
        'balance' => 0,
        'status' => $sale->status ?? 'draft',
    ];

    // Fetch sale items manually
    $saleItems = SaleItem::where('sale_id', $sale->id)->get();

    foreach ($saleItems as $item) {
        $this->selectItem($item->inventory_id, $item->id);
    }

    // Set account info
    $this->accounts = Account::where('id', $sale->account_id)->pluck('name', 'id')->toArray();

    // compute original sale total from sale items (same logic as mainCalculator uses)
$original_total = 0;
foreach ($saleItems as $item) {
    $lineGross = ($item->unit_price ?? 0) * ($item->quantity ?? 1);
    $lineNet = $lineGross - ($item->discount ?? 0);
    $lineTax = ($lineNet * ($item->tax ?? 0)) / 100;
    $lineTotal = round($lineNet + $lineTax, 2);
    $original_total += $lineTotal;
}
$this->original_total = round($original_total, 2);

// derive percentage from DB-stored other_discount (if present)
if (! empty($this->sale_returns['other_discount']) && $this->original_total > 0) {
    $this->discount_percentage = round(($this->sale_returns['other_discount'] / $this->original_total) * 100, 6);
} else {
    $this->discount_percentage = null;
}

// optional: keep a copy in sale_returns for blade use (not required but convenient)
$this->sale_returns['original_total'] = $this->original_total;
$this->sale_returns['discount_percentage'] = $this->discount_percentage;


    $this->mainCalculator();
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

    public function calculateTotals()
{
    $this->sub_total = $this->items->sum(function ($item) {
        return $item['quantity'] * $item['price'];
    });

    // Recalculate discount based on updated subtotal
    if (!empty($this->discount_percentage)) {
        $this->discount_amount = ($this->sub_total * $this->discount_percentage) / 100;
    } else {
        $this->discount_amount = 0;
    }

    // Final total
    $this->total = $this->sub_total - $this->discount_amount;
}


public function mainCalculator()
{
    $items = collect($this->items);
    $payments = collect($this->payments);

    $this->sale_returns['gross_amount'] = round($items->sum('gross_amount'), 2);
    $this->sale_returns['total_quantity'] = round($items->sum('quantity'), 2);
    $this->sale_returns['item_discount'] = round($items->sum('discount'), 2);
    $this->sale_returns['tax_amount'] = round($items->sum('tax_amount'), 2);

    // total as the sum of line totals (your code already sets each item.total)
    $this->sale_returns['total'] = round($items->sum('total'), 2);

    // If discount_percentage wasn't set earlier but a numeric other_discount exists,
    // try deriving percentage from original_total as fallback
    if ($this->discount_percentage === null && !empty($this->sale_returns['other_discount']) && $this->original_total > 0) {
        $this->discount_percentage = ($this->sale_returns['other_discount'] / $this->original_total) * 100;
    }

    // If we have a discount percentage, always recalculate other_discount from current total
    if (!empty($this->discount_percentage) && is_numeric($this->discount_percentage)) {
        $this->sale_returns['other_discount'] = round(($this->sale_returns['total'] * $this->discount_percentage) / 100, 2);
    } else {
        // ensure other_discount is numeric fallback
        $this->sale_returns['other_discount'] = round($this->sale_returns['other_discount'] ?? 0, 2);
    }

    $this->sale_returns['grand_total'] = round($this->sale_returns['total'] - $this->sale_returns['other_discount'], 2);

    $this->sale_returns['paid'] = round($payments->sum('amount'), 2);
    $this->sale_returns['balance'] = round($this->sale_returns['grand_total'] - $this->sale_returns['paid'], 2);

    // keep payment default amount in sync
    $this->payment['amount'] = round($this->sale_returns['balance'], 2);

    // keep sale_returns cache for blade (optional convenience)
    $this->sale_returns['discount_percentage'] = $this->discount_percentage;
    $this->sale_returns['original_total'] = $this->original_total;
}



    public function selectItem($inventory_id, $sale_item_id = null)
    {
        $inventory = Inventory::find($inventory_id);
        $saleItem = $sale_item_id ? SaleItem::find($sale_item_id) : null;
        $this->addToCart($inventory, $saleItem);
        $this->cartCalculator($inventory_id);
        if (in_array($this->payment_method_name, ['cash', 'card'])) {
            $this->selectPaymentMethod($this->payment_method_name);
        }
    }

    public function addToCart($inventory, $saleItem = null)
    {
        $inventory_id = $inventory->id;

        $single = [
            'key' => $inventory_id,
            'sale_item_id' => $saleItem?->id,
            'inventory_id' => $inventory_id,
            'barcode' => $inventory->barcode,
            'product_id' => $inventory->product_id,
            'name' => $inventory->product?->name,
            'unit_price' => $saleItem?->unit_price ?? $inventory->product?->mrp,
            'discount' => $saleItem?->discount ?? 0,
            'quantity' => 1,
            'tax' => $saleItem?->tax ?? $inventory->product?->tax,
        ];

        if (isset($this->items[$inventory_id])) {
            $this->items[$inventory_id]['quantity'] += 1;
        } else {
            $this->items[$inventory_id] = $single;
        }

        $this->singleCartCalculator($inventory_id);
        $this->mainCalculator();
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
                    throw new Exception($response['message']);
                }
            }
            unset($this->items[$index]);
            $this->mainCalculator();
            $this->dispatch('success', ['message' => 'Item removed successfully']);

            
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
                        throw new Exception($response['message']);
                    }
                }
            }
            $this->items = [];
            $this->mainCalculator();
            $this->dispatch('success', ['message' => 'All items removed successfully']);
        } catch (\Throwable $th) {
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function selectPaymentMethod($method)
    {
        $this->payment_method_name = $method;
        if ($method == 'custom') {
            $this->dispatch('SaleReturn-Custom-Payment-Modify', $this->sale_returns, $this->payments);
            return;
        }

        $account = Account::firstWhere('name', $method);
        if (! $account) {
            $this->dispatch('error', ['message' => 'Payment method not assigned to an account head']);
            return;
        }

        $this->payment['payment_method_id'] = $account->id;

        if ($this->table_id) {
            $this->sale_return->payments()->delete();
        }
        $this->payments = [];

        $this->payments[] = [
            'amount' => $this->sale_returns['grand_total'],
            'payment_method_id' => $account->id,
            'name' => $account->name,
        ];

        $this->mainCalculator();
    }

    public function addPayment()
    {
        if (! $this->payment['amount']) {
            $this->dispatch('error', ['message' => 'Please enter an amount']);
            return;
        }

        if (! $this->payment['payment_method_id']) {
            $this->dispatch('error', ['message' => 'Please select a payment method']);
            return;
        }

        if ($this->payment['amount'] > $this->sale_returns['balance']) {
            $this->dispatch('error', ['message' => "You can't pay more than the balance"]);
            return;
        }

        $account = Account::find($this->payment['payment_method_id']);
        $this->payments[] = [
            'amount' => $this->payment['amount'],
            'payment_method_id' => $account->id,
            'name' => $account->name,
        ];

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
                    throw new Exception($response['message']);
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
        'sale_returns.account_id.required' => 'The customer field is required',
        'sale_returns.date.required' => 'The date field is required',
    ];

    public function submit()
    {
        if (! $this->sale_returns['total_quantity']) {
            $this->dispatch('error', ['message' => 'Add at least one product to save the sale!']);
            return;
        }

        $payment_methods = collect($this->payments)->pluck('name')->implode(',');
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
                throw new Exception('Please add at least one item');
            }

            $this->sale_returns['status'] = $type;
            $this->sale_returns['items'] = $this->items;
            $this->sale_returns['payments'] = $this->payments;

            if ($this->sale_returns['balance'] < 0) {
                throw new Exception('Please check the payment amount');
            }

            $user_id = Auth::id();
           
                $response = (new CreateAction())->execute($this->sale_returns, $user_id);
            

            if (! $response['success']) {
                throw new Exception($response['message']);
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

      public function viewItems()
    {
        $this->dispatch('SaleReturn-View-Items-Component', $this->sale_returns['status'], $this->items);
    }

    public function editItem($index)
    {
        $this->dispatch('SaleReturn-Edit-Item-Component', $index, $this->items[$index]);
    }


    public function render()
    {
        return view('livewire.sale-return.pager');
    }
}
