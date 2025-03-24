<?php

namespace App\Livewire\Purchase;

use App\Actions\Purchase\PaymentAction;
use App\Models\Account;
use App\Models\Purchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class VendorPayment extends Component
{
    protected $listeners = ['Open-VendorPayment-Component' => 'open'];

    public $name;

    public $vendor_purchases = [];

    public $vendor_id;

    public $checkAll;

    public $payment;

    public $data = [];

    public $journal_entries = [];

    public $total;

    public $default_payment_method_id = 1;

    public $paymentMethods = 1;

    public $account_ids = [];

    public function open($name, $vendor_id)
    {
        $this->mount($name, $vendor_id);
        $this->dispatch('ToggleVendorPaymentModal');
    }

    public function mount($name = null, $vendor_id = null)
    {
        $this->account_ids['discount_id'] = DB::table('accounts')->where('name', 'Discount')->value('id');
        $this->account_ids['discount_id'] = DB::table('accounts')->where('name', 'Discount')->value('id');
        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();
        $this->name = $name;
        $this->vendor_id = $vendor_id;
        $this->vendor_purchases = [];
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
        if ($this->vendor_id) {
            $data = Purchase::where('balance', '>', 0)
                ->when($this->search ?? '', function ($query, $value) {
                    return $query->where(function ($q) use ($value): void {
                        $value = trim($value);
                        $q->where('purchases.grand_total', 'like', "%{$value}%")
                            ->orWhere('purchases.invoice_no', 'like', "%{$value}%");
                    });
                })
                ->when($this->branch_id ?? '', function ($query, $value) {
                    return $query->where('branch_id', $value);
                })
                ->when($this->vendor_id ?? '', function ($query, $value) {
                    return $query->where('account_id', $value);
                })
                ->select(
                    'purchases.id',
                    'purchases.invoice_no',
                    'purchases.total',
                    'purchases.other_discount',
                    'purchases.grand_total',
                    'purchases.paid',
                    'purchases.balance'
                );
            $totalRow = clone $data;

            $this->data = $data->get();
            foreach ($this->data as $value) {
                $this->vendor_purchases[$value->id] = [
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
        if (preg_match('/^vendor_purchases\..*/', $key)) {
            $indexes = explode('.', $key);
            $index = $indexes[1] ?? null;
            if (! is_numeric($value)) {
                $this->vendor_purchases[$index][$indexes[2]] = 0;
            }
        }
        if (in_array($key, ['payment.amount'])) {
            $this->amountSplit();
        }
        if ($key == 'checkAll') {
            foreach ($this->vendor_purchases as $key => $item) {
                $this->vendor_purchases[$key]['selected'] = $value;
                $this->vendor_purchases[$key]['payment'] = $value ? $this->vendor_purchases[$key]['balance'] : 0;
            }
            $this->selectionAmountCalculation();
            $this->amountSplit();
        }
        if (in_array($key, ['payment.selected'])) {
        }
    }

    public function selectAction($id)
    {
        if ($this->vendor_purchases[$id]['selected']) {
            $this->vendor_purchases[$id]['payment'] = $this->vendor_purchases[$id]['balance'];
        } else {
            $this->vendor_purchases[$id]['payment'] = 0;
        }
        $this->selectionAmountCalculation();
    }

    public function selectionAmountCalculation()
    {
        $vendor_purchases = collect($this->vendor_purchases);
        $selectedPurchases = $vendor_purchases->filter(function ($value, $id) {
            return $value['selected'] == true;
        });
        $balance = $selectedPurchases->sum('payment');
        $this->payment['amount'] = $balance;
    }

    public function amountSplit()
    {
        if (! is_numeric($this->payment['amount'])) {
            $this->payment['amount'] = 0;
        }
        $vendor_purchases = collect($this->vendor_purchases);
        $amount = $this->payment['amount'];
        $selectBills = $vendor_purchases->filter(function ($value, $id) {
            return $value['selected'] == true;
        });
        $vendor_purchases->filter(function ($value, $id) {
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
            $this->vendor_purchases[$id]['payment'] = round($payment, 2);
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
            $vendor_purchases = collect($this->vendor_purchases);
            $vendor_purchases_payment = round($vendor_purchases->sum('payment'), 2);
            $diff = round($this->payment['amount'] - $vendor_purchases_payment, 2);
            if ($diff) {
                throw new \Exception('Total amount and individual invoice amounts do not match mismatching amount is ('.$diff.')', 1);
            }
            foreach ($vendor_purchases as $purchase_id => $value) {
                if ($value['payment'] || $value['discount']) {
                    $response = (new PaymentAction())->execute($this->vendor_id, $this->name, $purchase_id, $value, $this->payment, Auth::id());
                    if (! $response['success']) {
                        throw new \Exception($response['message'], 1);
                    }
                }
            }
            DB::commit();
            $this->mount($this->name, $this->vendor_id);
            $this->dispatch('success', ['message' => 'Payment added successfully']);
            $this->dispatch('Purchase-Payments-Refresh-Component');
            $this->dispatch('ToggleVendorPaymentModal');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.purchase.vendor-payment');
    }
}
