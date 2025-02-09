<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\CreateAction;
use App\Actions\Sale\UpdateAction;
use App\Helpers\Facades\SaleHelper;
use App\Helpers\Facades\WhatsappHelper;
use App\Models\Account;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Sale-Custom-Payment-Confirmed' => 'collectPayments',
        'Sale-Edited-Item-Component' => 'editedItem',
        'Sale-selectItem-Component' => 'selectItem',
    ];

    public $categories;

    public $products;

    public $barcode_key;

    public $product_key;

    public $category_id;

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

    public $sales = [];

    public $default_payment_method_id = 1;

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();

        if (User::employee()->count() == 1) {
            $this->employees = User::employee()->pluck('name', 'id')->toArray();
            $this->employee_id = User::employee()->first(['id'])->id;
        }
        $this->payment_method_name = '';
        $this->payment = [
            'payment_method_id' => $this->default_payment_method_id,
            'payment_method_name' => '',
            'amount' => 0,
            'name' => null,
        ];

        if ($this->table_id) {
            $sales = Sale::with('account:id,name', 'branch:id,name', 'items.product:id,name', 'items.employee:id,name', 'createdUser:id,name', 'updatedUser:id,name', 'cancelledUser:id,name', 'payments.paymentMethod:id,name')->find($this->table_id);
            if (! $sales) {
                return redirect()->route('sale::index');
            }
            $this->sales = $sales->toArray();
            $this->accounts = Account::where('id', $this->sales['account_id'])->pluck('name', 'id')->toArray();
            $this->items = $sales->items->mapWithKeys(function ($item) {
                $key = $item['employee_id'].'-'.$item['inventory_id'];

                return [
                    $key => [
                        'id' => $item['id'],
                        'key' => $key,
                        'employee_id' => $item['employee_id'],
                        'inventory_id' => $item['inventory_id'],
                        'product_id' => $item['product_id'],
                        'name' => $item['name'],
                        'employee_name' => $item['employee_name'],
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

            $this->payments = $sales->payments->map->only(['id', 'amount', 'date', 'payment_method_id', 'created_by', 'name'])->toArray();
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
            ->when($this->category_key, function ($query, $value) {
                $query->where('name', 'LIKE', '%'.$value.'%');
            })
            ->whereNull('parent_id')
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
            $this->mainCalculator();
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
        $this->items[$key]['total'] = round($net_amount + $tax_amount, 2);
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

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->mainCalculator();
    }

    public function deleteAllItems()
    {
        $this->items = [];
        $this->mainCalculator();
    }

    public function removePayment($index)
    {
        unset($this->payments[$index]);
        $this->mainCalculator();
    }

    public function viewItems()
    {
        $this->dispatch('Sale-View-Items-Component', $this->sales['status'], $this->items);
    }

    public function editItem($index)
    {
        $this->dispatch('Sale-Edit-Item-Component', $index, $this->items[$index]);
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
            $this->dispatch('Sale-Custom-Payment-Modify', $this->sales, $this->payments);

            return false;
        }
        $account = Account::firstWhere('name', $method);
        if (! $account) {
            $this->dispatch('error', ['message' => 'The selected method has not been assigned to an account head']);

            return false;
        }
        $this->payment['payment_method_id'] = $account->id;
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

    public function save($type = 'completed')
    {
        $this->validate();
        try {
            $oldStatus = $this->sales['status'];
            DB::beginTransaction();
            if (! count($this->items)) {
                throw new \Exception('Please add any item', 1);
            }
            $this->sales['status'] = $type;
            $this->sales['items'] = $this->items;
            $this->sales['payments'] = $this->payments;

            $user_id = auth()->id();
            if (! $this->table_id) {
                $response = (new CreateAction)->execute($this->sales, $user_id);
            } else {
                $response = (new UpdateAction)->execute($this->sales, $this->table_id, $user_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $table_id = $response['data']['id'];
            $this->mount($this->table_id);

            DB::commit();
            if ($this->send_to_whatsapp) {
                $this->sendToWhatsapp($table_id);
            }
            if ($type == 'completed') {
                $this->dispatch('print-invoice', ['link' => route('print::sale::invoice', $response['data']['id'])]);
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
            default:
                return view('livewire.sale.page');
        }
    }
}
