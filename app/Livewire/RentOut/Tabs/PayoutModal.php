<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Payment\StorePaymentAction;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class PayoutModal extends Component
{
    public ?int $rentOutId = null;

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
        $this->form = [
            'date' => now()->format('Y-m-d'),
            'amount' => 0,
            'account_id' => '',
            'remark' => '',
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

            $response = (new StorePaymentAction())->execute([
                'rent_out_id' => $this->rentOutId,
                'date' => $this->form['date'],
                'credit' => 0,
                'debit' => $this->form['amount'],
                'account_id' => $this->form['account_id'],
                'source' => 'Payout',
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
