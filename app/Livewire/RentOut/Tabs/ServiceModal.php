<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Payment\StorePaymentAction;
use App\Models\RentOutPayment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ServiceModal extends Component
{
    public ?int $rentOutId = null;

    public ?int $editingId = null;

    public ?string $editCategoryName = null;

    public ?string $editAccountName = null;

    public array $form = [
        'date' => '',
        'amount' => 0,
        'category' => '',
        'account_id' => '',
        'remark' => '',
    ];

    #[On('open-service-modal')]
    public function openModal($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $this->editingId = null;
        $this->editCategoryName = null;
        $this->editAccountName = null;
        $this->form = [
            'date' => now()->format('Y-m-d'),
            'amount' => 0,
            'category' => '',
            'account_id' => '',
            'remark' => '',
        ];
        $this->resetValidation();
        $this->dispatch('ToggleServiceModal');
    }

    #[On('edit-service-payment')]
    public function editPayment($paymentId)
    {
        $payment = RentOutPayment::findOrFail($paymentId);
        $this->rentOutId = $payment->rent_out_id;
        $this->editingId = $payment->id;

        $this->form = [
            'date' => $payment->date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'amount' => $payment->debit > 0 ? $payment->debit : $payment->credit,
            'category' => $payment->category ?? '',
            'account_id' => $payment->account_id ?? '',
            'remark' => $payment->remark ?? '',
        ];

        // Resolve names for TomSelect pre-population
        $this->editCategoryName = $payment->category ? Account::find($payment->category)?->name : null;
        $this->editAccountName = $payment->account?->name;

        $this->resetValidation();
        $this->dispatch('ToggleServiceModal');
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

            $response = (new StorePaymentAction())->charge($this->rentOutId, $this->servicePaymentData());

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('ToggleServiceModal');
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

            $action = new StorePaymentAction();

            if ($this->editingId) {
                $response = $action->update($this->editingId, [
                    'date' => $this->form['date'],
                    'amount' => $this->form['amount'],
                    'category' => $this->form['category'],
                    'account_id' => $this->form['account_id'],
                    'remark' => $this->form['remark'] ?? '',
                ]);

                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }

                DB::commit();
                $this->dispatch('ToggleServiceModal');
                $this->dispatch('rent-out-updated');
                $this->dispatch('success', message: 'Service payment updated.');

                return;
            }

            $response = $action->chargeAndPay($this->rentOutId, $this->servicePaymentData());

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('ToggleServiceModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Service payment recorded (Pay Now).');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    protected function servicePaymentData(): array
    {
        return [
            'date' => $this->form['date'],
            'amount' => $this->form['amount'],
            'account_id' => $this->form['account_id'] ?? '',
            'source' => 'Service',
            'group' => 'Service',
            'category' => $this->form['category'],
            'payment_type' => 'Services',
            'remark' => $this->form['remark'] ?? '',
            'created_by' => auth()->id(),
        ];
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.service-modal', [
            'paymentMethods' => paymentMethodsOptions(),
        ]);
    }
}
