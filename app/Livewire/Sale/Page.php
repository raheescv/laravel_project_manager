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
use Illuminate\Support\Facades\DB;
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

    public function mount($table_id = null)
    {
        $this->category_id = 'favorite';
        $this->table_id = $table_id;
        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();

        if (User::employee()->count() > 0) {
            $this->employees = User::employee()->pluck('name', 'id')->toArray();
            $this->employee_id = User::employee()->first(['id'])->id;
        }
        $this->default_payment_method_id = Configuration::where('key', 'default_payment_method_id')->value('value') ?? 1;
        $this->payment_method_name = strtolower(Account::find($this->default_payment_method_id)->name);
        $this->payment = [
            'payment_method_id' => $this->default_payment_method_id,
            'payment_method_name' => $this->payment_method_name,
            'amount' => 0,
            'name' => null,
        ];

        if ($this->table_id) {

            $this->sale = Sale::with(
                'account:id,name',
                'branch:id,name',
                'items.product:id,name',
                'items.employee:id,name',
                'comboOffers.comboOffer:id,name',
                'createdUser:id,name',
                'updatedUser:id,name',
                'cancelledUser:id,name',
                'payments.paymentMethod:id,name'
            )->find($this->table_id);

            if (! $this->sale) {
                return redirect()->route('sale::index');
            }
            $this->sales = $this->sale->toArray();
            $this->accounts = Account::where('id', $this->sales['account_id'])->pluck('name', 'id')->toArray();
            $this->items = $this->sale->items->mapWithKeys(function ($item) {
                $key = $item['employee_id'].'-'.$item['inventory_id'];

                return [
                    $key => [
                        'id' => $item['id'],
                        'key' => $key,
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
                    ],
                ];
            })->toArray();

            $this->comboOffers = $this->sale->comboOffers->map(function ($package) {
                $items = collect($this->items)->filter(function ($item) use ($package) {
                    return $item['sale_combo_offer_id'] == $package['id'];
                })->map(function ($item) {
                    $item['combo_offer_price'] = $item['unit_price'] - $item['discount'];

                    return $item;
                })->toArray();

                return [
                    'id' => $package['id'],
                    'combo_offer_id' => $package['combo_offer_id'],
                    'amount' => $package['amount'],
                    'combo_offer_name' => $package->comboOffer?->name,
                    'items' => $items,
                ];
            })->toArray();

            $this->payments = $this->sale->payments->map->only(['id', 'amount', 'date', 'payment_method_id', 'created_by', 'name'])->toArray();
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
            $this->sales = [
                'date' => date('Y-m-d'),
                'due_date' => date('Y-m-d'),
                'sale_type' => 'normal',
                'account_id' => 3,
                'customer_name' => '',
                'customer_mobile' => '',

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
                'rating' => 0,
                'feedback_type' => 'compliment',
                'feedback' => null,
                'status' => 'draft',
            ];
            if (! app()->isProduction()) {
                $this->sales['customer_mobile'] = '+919633155669';
            }
        }
        $this->getCustomerDetails();
        $this->dispatch('SelectDropDownValues', $this->sales);
        $this->getCategories();
    }

    public function getCategories()
    {
        $this->categories = Category::withCount('products')
            ->having('products_count', '>', 0)
            ->when($this->category_key, function ($query, $value) {
                return $query->where('name', 'LIKE', '%'.$value.'%');
            })
            ->orderBy('name')
            ->get()
            ->toArray();
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
        $account = Account::find($this->sales['account_id']);
        $this->account_balance = $account->ledger()->latest('id')->value('balance');
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
        $gross_amount = $this->items[$key]['unit_price'] * $this->items[$key]['quantity'];
        $net_amount = $gross_amount - $this->items[$key]['discount'];
        $tax_amount = $net_amount * $this->items[$key]['tax'] / 100;

        $this->items[$key]['gross_amount'] = round($gross_amount, 2);
        $this->items[$key]['net_amount'] = round($net_amount, 2);
        $this->items[$key]['tax_amount'] = round($tax_amount, 2);
        $total = round($net_amount + $tax_amount, 2);
        $this->items[$key]['total'] = $total;
        if ($this->sales['other_discount'] && $this->sales['total']) {
            $discount_percentage = ($this->sales['other_discount'] / $this->sales['total']) * 100;
            $this->items[$key]['effective_total'] = round($total - ($discount_percentage * $total) / 100, 3);
        } else {
            $this->items[$key]['effective_total'] = $total;
        }
    }

    public function mainCalculator()
    {
        $items = collect($this->items);
        $payments = collect($this->payments);
        $this->sales['gross_amount'] = round($items->sum('gross_amount'), 2);
        $this->sales['total_quantity'] = round($items->sum('quantity'), 2);
        $this->sales['item_discount'] = round($items->sum('discount'), 2);
        $this->sales['tax_amount'] = round($items->sum('tax_amount'), 2);

        $this->sales['total'] = round($items->sum('total'), 2);

        $this->sales['grand_total'] = $this->sales['total'];
        $this->sales['grand_total'] -= $this->sales['other_discount'];
        $this->sales['grand_total'] += $this->sales['freight'];
        $this->sales['grand_total'] = round($this->sales['grand_total'], 2);

        $this->sales['paid'] = round($payments->sum('amount'), 2);
        $this->sales['balance'] = round($this->sales['grand_total'] - $this->sales['paid'], 2);
        $this->payment['amount'] = round($this->sales['balance'], 2);
    }

    public function selectItem($id)
    {
        $inventory = Inventory::find($id);
        if (! $this->employee_id) {
            $this->dispatch('error', ['message' => 'please select your employee first']);
            $this->dispatch('OpenEmployeeDropBox');

            return false;
        }
        $this->employee = User::find($this->employee_id);
        $this->employee_id = $this->employee->id;
        $this->addToCart($inventory);
        $this->cartCalculator($this->employee_id.'-'.$inventory->id);
        if (in_array($this->payment_method_name, ['cash', 'card'])) {
            $this->selectPaymentMethod($this->payment_method_name);
        }
        // $this->dispatch('OpenProductBox');
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

    public function addToCart($inventory)
    {
        $key = $this->employee_id.'-'.$inventory->id;
        $product_id = $inventory->product_id;
        $product = $inventory->product;
        $single = [
            'key' => $key,
            'inventory_id' => $inventory->id,
            'barcode' => $inventory->barcode,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee->name,
            'assistant_name' => '',
            'product_id' => $product_id,
            'name' => $product->name,
            'unit_price' => $product->mrp,
            'discount' => 0,
            'quantity' => 1,
            'tax' => 0,
        ];
        $saleTypePrice = $product->saleTypePrice($this->sales['sale_type']);
        $discount = $product->mrp - $saleTypePrice;

        if ($discount > 0) {
            $single['unit_price'] = $product->mrp;
            $single['discount'] = $discount;
        } else {
            $single['unit_price'] = $saleTypePrice;
        }

        if (isset($this->items[$key])) {
            $this->items[$key]['quantity'] += 1;
        } else {
            $this->items[$key] = $single;
        }
        $this->singleCartCalculator($key);
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

        $this->dispatch('show-confirmation', [
            'customer' => $customer,
            'grand_total' => currency($this->sales['grand_total']),
            'paid' => currency($this->sales['paid']),
            'payment_methods' => $payment_methods,
            'balance' => currency($this->sales['balance']),
        ]);
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
