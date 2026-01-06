<?php

namespace App\Livewire\Package;

use App\Actions\Package\Payment\CreateAction;
use App\Actions\Package\Payment\DeleteAction;
use App\Actions\Package\Payment\UpdateAction;
use App\Models\Account;
use App\Models\Package;
use App\Models\PackagePayment;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Payments extends Component
{
    public $package_id;

    public $payments = [];

    public $payment = [];

    public $showModal = false;

    public $editingId = null;

    public $paymentMethods = [];

    public function mount($package_id)
    {
        $this->package_id = $package_id;
        $this->loadPayments();
        $this->loadPaymentMethods();
    }

    public function loadPayments()
    {
        $package = Package::with('payments.paymentMethod')->find($this->package_id);
        $this->payments = $package ? $package->payments->toArray() : [];
    }

    public function loadPaymentMethods()
    {
        // Load payment methods from accounts (where is_payment_method = true or from cache)
        $this->paymentMethods = Account::whereIn('id', cache('payment_methods', []))->pluck('name', 'id')->toArray();
    }

    public function openModal($id = null)
    {
        $this->editingId = $id;
        if ($id) {
            $payment = PackagePayment::with('paymentMethod')->find($id);
            $this->payment = $payment->toArray();
        } else {
            $package = Package::find($this->package_id);
            $this->payment = [
                'package_id' => $this->package_id,
                'amount' => max(0, ($package->amount ?? 0) - ($package->paid ?? 0)),
                'payment_method_id' => '1',
                'date' => now()->format('Y-m-d'),
            ];
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->payment = [];
        $this->editingId = null;
    }

    public function save()
    {
        $this->validate([
            'payment.amount' => 'required|numeric|min:0.01',
            'payment.payment_method_id' => 'required|exists:accounts,id',
            'payment.date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();
            if ($this->editingId) {
                $response = (new UpdateAction())->execute($this->payment, $this->editingId);
            } else {
                $response = (new CreateAction())->execute($this->payment);
            }

            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            DB::commit();

            $this->dispatch('success', ['message' => $response['message']]);
            $this->loadPayments();
            $this->dispatch('package-payment-updated'); // Refresh parent component to update balance
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $response = (new DeleteAction())->execute($id);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
            $this->loadPayments();
            $this->dispatch('package-payment-updated'); // Refresh parent component to update balance
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.package.payments');
    }
}
