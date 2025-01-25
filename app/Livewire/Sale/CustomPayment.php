<?php

namespace App\Livewire\Sale;

use App\Models\Account;
use Livewire\Component;

class CustomPayment extends Component
{
    protected $listeners = [
        'Sale-Custom-Payment-Modify' => 'open',
    ];

    public $sales;

    public $payment;

    public $payments;

    public $paymentMethods = [];

    public $default_payment_method_id = 1;

    public function mount($sales = [], $payments = [])
    {
        if (! $sales) {
            $sales = [
                'status' => 'draft',
                'grand_total' => '0',
                'paid' => '0',
                'balance' => '0',
            ];
        }

        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();
        $this->sales = $sales;
        $this->payments = $payments;
        $this->payment = [
            'payment_method_id' => $this->default_payment_method_id,
            'payment_method_name' => '',
            'amount' => $sales['grand_total'],
            'name' => null,
        ];
    }

    public function open($total, $payments)
    {
        $this->mount($total, $payments);
        $this->dispatch('ToggleCustomPaymentModal');
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

    public function removePayment($index)
    {
        unset($this->payments[$index]);
        $this->mainCalculator();
    }

    public function mainCalculator()
    {
        $payments = collect($this->payments);
        $this->sales['paid'] = round($payments->sum('amount'), 2);
        $this->sales['balance'] = round($this->sales['grand_total'] - $this->sales['paid'], 2);
        $this->payment['amount'] = round($this->sales['balance'], 2);
    }

    public function save()
    {
        $this->dispatch('Sale-Custom-Payment-Confirmed', $this->sales, $this->payments);
        $this->dispatch('ToggleCustomPaymentModal');
    }

    public function render()
    {
        return view('livewire.sale.custom-payment');
    }
}
