<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Payment\StorePaymentAction;
use App\Models\RentOut;
use App\Models\RentOutPayment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ServicePaymentModal extends Component
{
    public ?int $rentOutId = null;

    public array $form = [
        'date' => '',
        'amount' => 0,
        'category' => '',
        'account_id' => '',
        'remark' => '',
    ];

    #[On('open-service-payment-modal')]
    public function openModal($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $this->form = [
            'date' => now()->format('Y-m-d'),
            'amount' => 0,
            'category' => '',
            'account_id' => '',
            'remark' => '',
        ];
        $this->resetValidation();
        $this->dispatch('ToggleServicePaymentModal');
    }

    public function payLater()
    {
        $this->validate([
            'form.date' => 'required|date',
            'form.amount' => 'required|numeric|min:0.01',
            'form.category' => 'required|string',
        ], [
            'form.date.required' => 'Date is required.',
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
            'form.category.required' => 'Category is required.',
        ]);

        try {
            DB::beginTransaction();

            $rentOut = RentOut::findOrFail($this->rentOutId);

            // Single debit entry — charge to customer, no payment received
            $response = (new StorePaymentAction())->execute([
                'rent_out_id' => $this->rentOutId,
                'date' => $this->form['date'],
                'credit' => 0,
                'debit' => $this->form['amount'],
                'account_id' => $rentOut->account_id,
                'source' => 'Service',
                'group' => 'Service',
                'category' => $this->form['category'],
                'payment_type' => 'Services',
                'remark' => $this->form['remark'] ?? '',
                'created_by' => auth()->id(),
            ]);

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('ToggleServicePaymentModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Service charge recorded (Pay Later).');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function payNow()
    {
        $this->validate([
            'form.date' => 'required|date',
            'form.amount' => 'required|numeric|min:0.01',
            'form.category' => 'required|string',
            'form.account_id' => 'required',
        ], [
            'form.date.required' => 'Date is required.',
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
            'form.category.required' => 'Category is required.',
            'form.account_id.required' => 'Payment method is required.',
        ]);

        try {
            DB::beginTransaction();

            $rentOut = RentOut::findOrFail($this->rentOutId);

            // Entry 1: Debit — charge to customer
            $response = (new StorePaymentAction())->execute([
                'rent_out_id' => $this->rentOutId,
                'date' => $this->form['date'],
                'credit' => 0,
                'debit' => $this->form['amount'],
                'account_id' => $rentOut->account_id,
                'source' => 'Service',
                'group' => 'Service',
                'category' => $this->form['category'],
                'payment_type' => 'Services',
                'remark' => $this->form['remark'] ?? '',
                'created_by' => auth()->id(),
            ]);

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            // Entry 2: Credit — payment received
            $response = (new StorePaymentAction())->execute([
                'rent_out_id' => $this->rentOutId,
                'date' => $this->form['date'],
                'credit' => $this->form['amount'],
                'debit' => 0,
                'account_id' => $this->form['account_id'],
                'source' => 'Service',
                'group' => 'Service Payment',
                'category' => $this->form['category'],
                'payment_type' => 'Services',
                'remark' => $this->form['remark'] ?? '',
                'created_by' => auth()->id(),
            ]);

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('ToggleServicePaymentModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Service payment recorded (Pay Now).');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $serviceCharges = collect();
        if ($this->rentOutId) {
            $serviceCharges = RentOutPayment::where('rent_out_id', $this->rentOutId)
                ->whereIn('source', ['Service', 'ServiceCharge'])
                ->selectRaw('category, sum(credit) as credit, sum(debit) as debit')
                ->groupBy('category')
                ->get();
        }

        return view('livewire.rent-out.tabs.service-payment-modal', [
            'paymentMethods' => paymentMethodsOptions(),
            'serviceCharges' => $serviceCharges,
        ]);
    }
}
