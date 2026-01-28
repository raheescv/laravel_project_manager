<?php

namespace App\Livewire\SaleReturn;

use App\Actions\SaleReturn\PaymentAction;
use App\Models\Account;
use App\Models\SaleReturn;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CustomerPayment extends Component
{
    protected $listeners = ['Open-CustomerPayment-Component' => 'open'];

    public $name;

    public $customer_sale_returns = [];

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
        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();
        $this->name = $name;
        $this->customer_id = $customer_id;
        $this->customer_sale_returns = [];
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
            $data = SaleReturn::where('balance', '>', 0)
                ->when($this->search ?? '', function ($query, $value) {
                    return $query->where(function ($q) use ($value): void {
                        $value = trim($value);
                        $q->where('sale_returns.grand_total', 'like', "%{$value}%")
                            ->orWhere('sale_returns.reference_no', 'like', "%{$value}%")
                            ->orWhere('sale_returns.id', 'like', "%{$value}%");
                    });
                })
                ->when($this->branch_id ?? '', function ($query, $value) {
                    return $query->where('branch_id', $value);
                })
                ->when($this->customer_id ?? '', function ($query, $value) {
                    return $query->where('account_id', $value);
                })
                ->select(
                    'sale_returns.id',
                    'sale_returns.reference_no',
                    'sale_returns.total',
                    'sale_returns.other_discount',
                    'sale_returns.grand_total',
                    'sale_returns.paid',
                    'sale_returns.balance'
                );
            $totalRow = clone $data;

            $this->data = $data->get();
            foreach ($this->data as $value) {
                $this->customer_sale_returns[$value->id] = [
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
        if (preg_match('/^customer_sale_returns\..*/', $key)) {
            $indexes = explode('.', $key);
            $index = $indexes[1] ?? null;
            if (! is_numeric($value)) {
                if ($indexes[2] != 'selected') {
                    $this->customer_sale_returns[$index][$indexes[2]] = 0;
                }
            }
        }
        if (in_array($key, ['payment.amount'])) {
            $this->amountSplit();
        }
        if ($key == 'checkAll') {
            foreach ($this->customer_sale_returns as $key => $item) {
                $this->customer_sale_returns[$key]['selected'] = $value;
                $this->customer_sale_returns[$key]['payment'] = $value ? $this->customer_sale_returns[$key]['balance'] : 0;
            }
            $this->selectionAmountCalculation();
            $this->amountSplit();
        }
        if (in_array($key, ['payment.selected'])) {
        }
    }

    public function selectAction($id)
    {
        if ($this->customer_sale_returns[$id]['selected']) {
            $this->customer_sale_returns[$id]['payment'] = $this->customer_sale_returns[$id]['balance'];
        } else {
            $this->customer_sale_returns[$id]['payment'] = 0;
        }
        $this->selectionAmountCalculation();
    }

    public function selectionAmountCalculation()
    {
        $customer_sale_returns = collect($this->customer_sale_returns);
        $selectedSales = $customer_sale_returns->filter(function ($value, $id) {
            return $value['selected'] == true;
        });
        $balance = $selectedSales->sum('payment');
        $this->payment['amount'] = $balance;
    }

    public function amountSplit()
    {
        if (! is_numeric($this->payment['amount'])) {
            $this->payment['amount'] = 0;
        }
        $customer_sale_returns = collect($this->customer_sale_returns);
        $amount = $this->payment['amount'];
        $selectBills = $customer_sale_returns->filter(function ($value, $id) {
            return $value['selected'] == true;
        });
        $customer_sale_returns->filter(function ($value, $id) {
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
            $this->customer_sale_returns[$id]['payment'] = round($payment, 2);
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
            $paid_flag = 0;
            $customer_sale_returns = collect($this->customer_sale_returns);
            $customer_sales_payment = round($customer_sale_returns->sum('payment'), 2);
            $diff = round($this->payment['amount'] - $customer_sales_payment, 2);
            if ($diff) {
                throw new Exception('Total amount and individual invoice amounts do not match mismatching amount is ('.$diff.')', 1);
            }
            $paymentIds = [];
            $receiptData = [];
            foreach ($customer_sale_returns as $sale_return_id => $value) {
                if ($value['payment'] || $value['discount']) {
                    $saleReturn = SaleReturn::find($sale_return_id);
                    $response = (new PaymentAction())->execute($this->customer_id, $this->name, $sale_return_id, $value, $this->payment, Auth::id());
                    if (! $response['success']) {
                        throw new Exception($response['message'], 1);
                    }
                    // Collect payment data for receipt printing
                    if ($value['payment'] > 0 && $saleReturn) {
                        $paymentId = $response['data']['payment_id'] ?? null;
                        if ($paymentId) {
                            $paymentIds[] = $paymentId;
                        }
                        $receiptData[] = [
                            'id' => $saleReturn->id ?? '',
                            'reference_no' => $saleReturn->reference_no ?? '',
                            'amount' => $value['payment'],
                            'discount' => $value['discount'] ?? 0,
                        ];
                    }
                }
            }
            DB::commit();
            $payment = $this->payment;
            $this->mount($this->name, $this->customer_id);
            $this->dispatch('success', ['message' => 'Payment added successfully']);
            $this->dispatch('SaleReturn-Payments-Refresh-Component');
            $this->dispatch('ToggleCustomerReceiptModal');

            // Trigger print if there are payments
            if (! empty($paymentIds) && ! empty($receiptData)) {
                $this->dispatch('print-sale-return-payment-receipt', [
                    'customer_name' => $this->name,
                    'payment_date' => $payment['date'],
                    'payment_method' => $payment['payment_method_id'],
                    'total_amount' => $payment['amount'],
                    'receipt_data' => $receiptData,
                    'payment_ids' => array_filter($paymentIds),
                ]);
            }
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.sale-return.customer-payment');
    }
}
