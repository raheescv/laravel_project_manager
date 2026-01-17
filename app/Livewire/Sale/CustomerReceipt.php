<?php

namespace App\Livewire\Sale;

use App\Actions\Sale\ReceiptAction;
use App\Models\Account;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CustomerReceipt extends Component
{
    protected $listeners = ['Open-CustomerReceipt-Component' => 'open'];

    public $name;

    public $customer_sales = [];

    public $customer_id;

    public $checkAll;

    public $payment;

    public $data = [];

    public $journal_entries = [];

    public $total;

    public $default_payment_method_id = 1;

    public $paymentMethods = 1;

    public $account_ids = [];

    public function open($name, $customer_id)
    {
        $this->mount($name, $customer_id);
        $this->dispatch('ToggleCustomerReceiptModal');
    }

    public function mount($name = null, $customer_id = null)
    {
        $this->account_ids['discount_id'] = DB::table('accounts')->where('name', 'Discount')->value('id');
        $this->account_ids['discount_id'] = DB::table('accounts')->where('name', 'Discount')->value('id');
        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();
        $this->name = $name;
        $this->customer_id = $customer_id;
        $this->customer_sales = [];
        $this->payment = [
            'date' => date('Y-m-d'),
            'amount' => 0,
            'remarks' => '',
            'payment_method_id' => 1,
        ];
        $this->total = [
            'total' => 0,
            'other_discount' => 0,
            'grand_total' => 0,
            'paid' => 0,
            'balance' => 0,
        ];
        if ($this->customer_id) {
            $data = Sale::where('balance', '>', 0)
                ->when($this->search ?? '', function ($query, $value) {
                    return $query->where(function ($q) use ($value): void {
                        $value = trim($value);
                        $q->where('sales.grand_total', 'like', "%{$value}%")
                            ->orWhere('sales.invoice_no', 'like', "%{$value}%");
                    });
                })
                ->when($this->branch_id ?? '', function ($query, $value) {
                    return $query->where('branch_id', $value);
                })
                ->when($this->customer_id ?? '', function ($query, $value) {
                    return $query->where('account_id', $value);
                })
                ->select(
                    'sales.id',
                    'sales.invoice_no',
                    'sales.total',
                    'sales.other_discount',
                    'sales.grand_total',
                    'sales.paid',
                    'sales.balance'
                );
            $totalRow = clone $data;

            $this->data = $data->get();
            foreach ($this->data as $value) {
                $this->customer_sales[$value->id] = [
                    'balance' => $value->balance,
                    'payment' => 0,
                    'discount' => 0,
                    'selected' => false,
                ];
            }
            $this->total['total'] = $totalRow->sum('total');
            $this->total['other_discount'] = $totalRow->sum('other_discount');
            $this->total['grand_total'] = $totalRow->sum('grand_total');
            $this->total['paid'] = $totalRow->sum('paid');
            $this->total['balance'] = $totalRow->sum('balance');
        }
    }

    public function updated($key, $value)
    {
        if (preg_match('/^customer_sales\..*/', $key)) {
            $indexes = explode('.', $key);
            $index = $indexes[1] ?? null;
            if (! is_numeric($value)) {
                if ($indexes[2] != 'selected') {
                    $this->customer_sales[$index][$indexes[2]] = 0;
                }
            }
        }
        if (in_array($key, ['payment.amount'])) {
            $this->amountSplit();
        }
        if ($key == 'checkAll') {
            foreach ($this->customer_sales as $key => $item) {
                $this->customer_sales[$key]['selected'] = $value;
                $this->customer_sales[$key]['payment'] = $value ? $this->customer_sales[$key]['balance'] : 0;
            }
            $this->selectionAmountCalculation();
            $this->amountSplit();
        }
        if (in_array($key, ['payment.selected'])) {
        }
    }

    public function selectAction($id)
    {
        if ($this->customer_sales[$id]['selected']) {
            $this->customer_sales[$id]['payment'] = $this->customer_sales[$id]['balance'];
        } else {
            $this->customer_sales[$id]['payment'] = 0;
        }
        $this->selectionAmountCalculation();
    }

    public function selectionAmountCalculation()
    {
        $customer_sales = collect($this->customer_sales);
        $selectedSales = $customer_sales->filter(function ($value, $id) {
            return $value['selected'] == true;
        });
        $balance = $selectedSales->sum('payment');
        $this->payment['amount'] = round($balance, 2);
    }

    public function amountSplit()
    {
        if (! is_numeric($this->payment['amount'])) {
            $this->payment['amount'] = 0;
        }
        $customer_sales = collect($this->customer_sales);
        $amount = $this->payment['amount'];
        $selectBills = $customer_sales->filter(function ($value, $id) {
            return $value['selected'] == true;
        });
        $customer_sales->filter(function ($value, $id) {
            if (! $value['selected']) {
                $value['payment'] = 0;
            }

            return true;
        });
        foreach ($selectBills as $id => $value) {
            $balance = $value['balance'];
            if ($balance > $amount) {
                $payment = $amount;
                $amount = 0;
            } else {
                $payment = $balance;
                $amount -= $balance;
            }
            $this->customer_sales[$id]['payment'] = round($payment, 2);
        }
        if ($amount > 0) {
            $this->payment['amount'] -= $amount;
            $this->payment['amount'] = round($this->payment['amount'], 2);
        }
    }

    protected $rules = [
        'payment.payment_method_id' => ['required'],
        'payment.date' => ['required'],
        'payment.amount' => ['required'],
    ];

    protected $messages = [
        'payment.payment_method_id' => 'The payment method field is required',
        'payment.date' => 'The date field is required',
        'payment.amount' => 'The amount field is required',
    ];

    public function save()
    {
        $this->validate();
        try {
            DB::beginTransaction();
            $customer_sales = collect($this->customer_sales);
            $customer_sales_payment = round($customer_sales->sum('payment'), 2);
            $diff = round($this->payment['amount'] - $customer_sales_payment, 2);
            if ($diff) {
                throw new \Exception('Total amount and individual invoice amounts do not match mismatching amount is ('.$diff.')', 1);
            }
            foreach ($customer_sales as $sale_id => $value) {
                if ($value['payment'] || $value['discount']) {
                    $response = (new ReceiptAction())->execute($this->customer_id, $this->name, $sale_id, $value, $this->payment, Auth::id());
                    if (! $response['success']) {
                        throw new \Exception($response['message'], 1);
                    }
                }
            }
            DB::commit();
            $this->mount($this->name, $this->customer_id);
            $this->dispatch('success', ['message' => 'Payment added successfully']);
            $this->dispatch('Sale-Receipts-Refresh-Component');
            $this->dispatch('ToggleCustomerReceiptModal');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.sale.customer-receipt');
    }
}
