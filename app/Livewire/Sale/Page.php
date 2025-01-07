<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\CreateAction;
use App\Actions\Sale\UpdateAction;
use App\Models\Account;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public $table_id;

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
        $this->payment = [
            'payment_method_id' => $this->default_payment_method_id,
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
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
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
                'account_id' => 3,
                'gross_amount' => 0,
                'total_quantity' => 0,
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
        $this->dispatch('SelectDropDownValues', $this->sales);
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
        if (in_array($key, ['sales.other_discount', 'sales.freight'])) {
            if (! is_numeric($value)) {
                $index = explode('.', $key);
                $this->sales[$index[1]] = 0;
            }
            $this->mainCalculator();
        }
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

    public function addToCart($inventory)
    {
        $key = $this->employee_id.'-'.$inventory->id;
        $product_id = $inventory->product_id;
        $single = [
            'key' => $key,
            'inventory_id' => $inventory->id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee->name,
            'product_id' => $product_id,
            'name' => $inventory->product->name,
            'unit_price' => $inventory->product->mrp,
            'discount' => 0,
            'quantity' => 1,
            'tax' => 0,
        ];
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
        unset($this->items[$index]);
        $this->mainCalculator();
    }

    public function removePayment($index)
    {
        unset($this->payments[$index]);
        $this->mainCalculator();
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
        $this->dispatch('show-confirmation', [
            'grand_total' => currency($this->sales['grand_total']),
            'paid' => currency($this->sales['paid']),
            'balance' => currency($this->sales['balance']),
        ]);
    }

    public function save($type = 'completed')
    {
        $this->validate();
        try {
            $oldStatus = $this->sales['status'];
            DB::beginTransaction();
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

            $this->mount($this->table_id);
            DB::commit();
            $this->dispatch('ResetSelectBox');
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
            $this->sales['status'] = $oldStatus;
        }
    }

    public function render()
    {
        return view('livewire.sale.page');
    }
}
