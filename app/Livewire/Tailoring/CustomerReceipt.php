<?php

namespace App\Livewire\Tailoring;

use App\Actions\Tailoring\ReceiptAction;
use App\Models\Account;
use App\Models\TailoringOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CustomerReceipt extends Component
{
    protected $listeners = ['Open-TailoringCustomerReceipt' => 'open'];

    public $display_name = '';

    public $account_id = null;

    public $customer_name = '';

    public $customer_mobile = '';

    public $order_orders = [];  // order_id => ['balance' => x, 'payment' => x, 'selected' => bool]

    public $checkAll = false;

    public $payment = [];

    public $data = [];

    public $total = [];

    public $default_payment_method_id = 1;

    public $paymentMethods = [];

    public function mount(): void
    {
        $ids = cache('payment_methods', []);
        $this->paymentMethods = $ids
            ? Account::whereIn('id', $ids)->pluck('name', 'id')->toArray()
            : Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();

        $this->default_payment_method_id = array_key_exists($this->default_payment_method_id, $this->paymentMethods)
            ? $this->default_payment_method_id
            : (array_key_first($this->paymentMethods) ?: 1);

        $this->payment = [
            'date' => date('Y-m-d'),
            'amount' => 0,
            'remarks' => '',
            'payment_method_id' => $this->default_payment_method_id,
        ];
    }

    public function open($payload)
    {
        if (is_array($payload)) {
            $this->account_id = $payload['account_id'] ?? null;
            $this->customer_name = $payload['customer_name'] ?? '';
            $this->customer_mobile = $payload['customer_mobile'] ?? '';
            $this->display_name = $payload['display_name'] ?? ($this->customer_name ?: 'Customer');
        }
        $this->loadOrders();
        $this->dispatch('ToggleTailoringCustomerReceiptModal');
    }

    public function loadOrders()
    {
        $defaultPaymentMethodId = $this->default_payment_method_id;
        $this->payment = [
            'date' => date('Y-m-d'),
            'amount' => 0,
            'remarks' => '',
            'payment_method_id' => $defaultPaymentMethodId,
        ];
        $this->total = [
            'total' => 0,
            'other_discount' => 0,
            'grand_total' => 0,
            'paid' => 0,
            'balance' => 0,
        ];
        $this->order_orders = [];
        $this->data = [];

        $query = TailoringOrder::where('tailoring_orders.balance', '>', 0);
        if ($this->account_id) {
            $query->where('tailoring_orders.account_id', $this->account_id);
        } else {
            $query->whereNull('tailoring_orders.account_id')
                ->where('tailoring_orders.customer_name', $this->customer_name)
                ->where(function ($q) {
                    if ((string) $this->customer_mobile === '') {
                        $q->whereNull('tailoring_orders.customer_mobile')
                            ->orWhere('tailoring_orders.customer_mobile', '');
                    } else {
                        $q->where('tailoring_orders.customer_mobile', $this->customer_mobile);
                    }
                });
        }

        $this->data = $query->select(
            'tailoring_orders.id',
            'tailoring_orders.order_no',
            'tailoring_orders.total',
            'tailoring_orders.other_discount',
            'tailoring_orders.grand_total',
            'tailoring_orders.paid',
            'tailoring_orders.balance'
        )->orderBy('tailoring_orders.order_date')->get();

        $totalRow = clone $query;
        $this->total['total'] = $totalRow->sum('tailoring_orders.total');
        $this->total['other_discount'] = $totalRow->sum('tailoring_orders.other_discount');
        $this->total['grand_total'] = $totalRow->sum('tailoring_orders.grand_total');
        $this->total['paid'] = $totalRow->sum('tailoring_orders.paid');
        $this->total['balance'] = $totalRow->sum('tailoring_orders.balance');

        foreach ($this->data as $order) {
            $this->order_orders[$order->id] = [
                'balance' => (float) $order->balance,
                'payment' => 0,
                'selected' => false,
            ];
        }

    }

    public function updated($key, $value)
    {
        if (preg_match('/^order_orders\..*/', $key)) {
            $parts = explode('.', $key);
            $id = $parts[1] ?? null;
            if (isset($parts[2]) && $parts[2] === 'payment' && ! is_numeric($value)) {
                $this->order_orders[$id]['payment'] = 0;
            }
        }
        if ($key === 'payment.amount') {
            $this->amountSplit();
        }
        if ($key === 'checkAll') {
            foreach ($this->order_orders as $id => $item) {
                $this->order_orders[$id]['selected'] = $value;
                $this->order_orders[$id]['payment'] = $value ? $this->order_orders[$id]['balance'] : 0;
            }
            $this->selectionAmountCalculation();
            $this->amountSplit();
        }
    }

    public function selectAction($id)
    {
        if ($this->order_orders[$id]['selected']) {
            $this->order_orders[$id]['payment'] = $this->order_orders[$id]['balance'];
        } else {
            $this->order_orders[$id]['payment'] = 0;
        }
        $this->selectionAmountCalculation();
    }

    public function selectionAmountCalculation()
    {
        $selected = collect($this->order_orders)->filter(fn ($v) => $v['selected'])->sum('payment');
        $this->payment['amount'] = round($selected, 2);
    }

    public function amountSplit()
    {
        if (! is_numeric($this->payment['amount'])) {
            $this->payment['amount'] = 0;
        }
        $amount = (float) $this->payment['amount'];
        $selected = collect($this->order_orders)->filter(fn ($v) => $v['selected']);
        foreach (array_keys($this->order_orders) as $id) {
            if (! $this->order_orders[$id]['selected']) {
                $this->order_orders[$id]['payment'] = 0;
            }
        }
        foreach ($selected as $id => $item) {
            $balance = $item['balance'];
            if ($balance > $amount) {
                $payment = $amount;
                $amount = 0;
            } else {
                $payment = $balance;
                $amount -= $balance;
            }
            $this->order_orders[$id]['payment'] = round($payment, 2);
        }
        if ($amount > 0) {
            $this->payment['amount'] = round($this->payment['amount'] - $amount, 2);
        }
    }

    protected function rules()
    {
        return [
            'payment.payment_method_id' => ['required'],
            'payment.date' => ['required', 'date'],
            'payment.amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function messages()
    {
        return [
            'payment.payment_method_id.required' => 'The payment method field is required.',
            'payment.date.required' => 'The date field is required.',
            'payment.amount.required' => 'The amount field is required.',
        ];
    }

    public function save()
    {
        $this->validate();
        try {
            DB::beginTransaction();
            $totalAllocated = round(collect($this->order_orders)->sum('payment'), 2);
            $diff = round((float) $this->payment['amount'] - $totalAllocated, 2);
            if (abs($diff) > 0.01) {
                throw new \Exception('Total amount and individual order amounts do not match. Difference: '.$diff);
            }
            $paymentIds = [];
            $receiptData = [];
            foreach ($this->order_orders as $orderId => $row) {
                if (($row['payment'] ?? 0) <= 0) {
                    continue;
                }
                $order = TailoringOrder::find($orderId);
                if (! $order) {
                    continue;
                }
                $response = (new ReceiptAction())->execute(
                    (int) $orderId,
                    $this->display_name,
                    (float) $row['payment'],
                    [
                        'date' => $this->payment['date'],
                        'payment_method_id' => (int) $this->payment['payment_method_id'],
                        'remarks' => $this->payment['remarks'] ?? '',
                    ],
                    Auth::id()
                );
                if (! ($response['success'] ?? false)) {
                    throw new \Exception($response['message'] ?? 'Failed to create payment');
                }
                $paymentIds[] = ($response['data'] ?? null)?->id ?? null;
                $receiptData[] = [
                    'invoice_no' => $order->order_no,
                    'amount' => $row['payment'],
                    'discount' => 0,
                ];
            }
            $payment = $this->payment;
            DB::commit();
            $this->loadOrders();
            $this->dispatch('success', ['message' => 'Payment(s) added successfully']);
            $this->dispatch('Tailoring-Receipts-Refresh');
            $this->dispatch('ToggleTailoringCustomerReceiptModal');

            if (! empty($paymentIds) && ! empty($receiptData)) {
                $this->dispatch('print-tailoring-customer-receipt', [
                    'customer_name' => $this->display_name,
                    'payment_date' => $payment['date'],
                    'payment_method' => $payment['payment_method_id'],
                    'total_amount' => $payment['amount'],
                    'receipt_data' => $receiptData,
                    'payment_ids' => array_filter($paymentIds),
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.tailoring.customer-receipt');
    }
}
