<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Payment\StoreTransactionAction;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class PayoutModal extends Component
{
    public ?int $rentOutId = null;

    public ?int $editingId = null;

    public array $form = [
        'date' => '',
        'amount' => 0,
        'account_id' => '',
        'remark' => '',
    ];

    #[On('open-payout-modal')]
    public function openModal($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $this->editingId = null;
        $this->form = [
            'date' => now()->format('Y-m-d'),
            'amount' => 0,
            'account_id' => '',
            'remark' => '',
        ];
        $this->resetValidation();
        $this->dispatch('TogglePayoutModal');
    }

    #[On('edit-payout-payment')]
    public function editPayment($paymentId)
    {
        $payment = RentOutTransaction::findOrFail($paymentId);
        $this->rentOutId = $payment->rent_out_id;
        $this->editingId = $payment->id;

        $this->form = [
            'date' => $payment->date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'amount' => $payment->debit > 0 ? $payment->debit : $payment->credit,
            'account_id' => $payment->account_id ?? '',
            'remark' => $payment->remark ?? '',
        ];

        $this->resetValidation();
        $this->dispatch('TogglePayoutModal');
    }

    public function save()
    {
        $this->validate([
            'form.date' => 'required|date',
            'form.amount' => 'required|numeric|min:0.01',
            'form.account_id' => 'required',
        ], [
            'form.date.required' => 'Date is required.',
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
            'form.account_id.required' => 'Payment method is required.',
        ]);

        try {
            DB::beginTransaction();

            $action = new StoreTransactionAction();

            if ($this->editingId) {
                $response = $action->update($this->editingId, [
                    'date' => $this->form['date'],
                    'amount' => $this->form['amount'],
                    'account_id' => $this->form['account_id'],
                    'remark' => $this->form['remark'] ?? '',
                ]);

                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }

                DB::commit();
                $this->dispatch('TogglePayoutModal');
                $this->dispatch('rent-out-updated');
                $this->dispatch('success', message: 'Payment updated successfully.');

                return;
            }

            $response = $action->execute([
                'rent_out_id' => $this->rentOutId,
                'date' => $this->form['date'],
                'credit' => 0,
                'debit' => $this->form['amount'],
                'account_id' => $this->form['account_id'],
                'source' => 'Payout',
                'model' => 'RentOut',
                'model_id' => $this->rentOutId,
                'paid_date' => $this->form['date'],
                'reason' => $this->form['remark'] ?: 'Payout',
                'group' => 'Payout',
                'category' => 'Payout',
                'payment_type' => 'Payout',
                'remark' => $this->form['remark'] ?? '',
                'created_by' => auth()->id(),
            ]);

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('TogglePayoutModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Payout recorded successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.payout-modal', [
            'paymentMethods' => paymentMethodsOptions(),
        ]);
    }
}
