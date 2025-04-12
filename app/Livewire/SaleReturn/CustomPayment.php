<?php

namespace App\Livewire\SaleReturn;

use App\Actions\SaleReturn\Payment\DeleteAction;
use App\Models\Account;
use Livewire\Component;

class CustomPayment extends Component
{
    protected $listeners = [
        'SaleReturn-Custom-Payment-Modify' => 'open',
    ];

    public $sale_returns;

    public $payment;

    public $payments;

    public $paymentMethods = [];

    public $default_payment_method_id = 1;

    public function mount($sale_returns = [], $payments = [])
    {
        if (! $sale_returns) {
            $sale_returns = [
                'status' => 'draft',
                'grand_total' => '0',
                'paid' => '0',
                'balance' => '0',
            ];
        }

        $this->paymentMethods = Account::where('id', $this->default_payment_method_id)->pluck('name', 'id')->toArray();
        $this->sale_returns = $sale_returns;
        $this->payments = $payments;
        $this->payment = [
            'payment_method_id' => $this->default_payment_method_id,
            'payment_method_name' => '',
            'amount' => $sale_returns['balance'],
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
        if ($this->payment['amount'] > $this->sale_returns['balance']) {
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
        try {
            $id = $this->payments[$index]['id'] ?? '';
            if ($id) {
                $response = (new DeleteAction())->execute($id);
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

    public function mainCalculator()
    {
        $payments = collect($this->payments);
        $this->sale_returns['paid'] = round($payments->sum('amount'), 2);
        $this->sale_returns['balance'] = round($this->sale_returns['grand_total'] - $this->sale_returns['paid'], 2);
        $this->payment['amount'] = round($this->sale_returns['balance'], 2);
    }

    public function save()
    {
        $this->dispatch('SaleReturn-Custom-Payment-Confirmed', $this->sale_returns, $this->payments);
        $this->dispatch('ToggleCustomPaymentModal');
    }

    public function render()
    {
        return view('livewire.sale-return.custom-payment');
    }
}
