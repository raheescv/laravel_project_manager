<?php

namespace App\Livewire\RentOut\Tabs;

use App\Helpers\Facades\RentOutTransactionHelper;
use App\Models\RentOutRoi;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class RoiPaymentModal extends Component
{
    public ?int $rentOutId = null;

    public float $balance = 0;

    public array $form = [
        'date' => '',
        'amount' => 0,
        'account_id' => '',
        'remark' => '',
    ];

    #[On('open-roi-payment-modal')]
    public function openModal($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $this->balance = RentOutRoi::totalsFor($rentOutId)['balance'];
        $this->form = [
            'date' => now()->format('Y-m-d'),
            'amount' => max($this->balance, 0),
            'account_id' => '',
            'remark' => '',
        ];
        $this->resetValidation();
        $this->dispatch('ToggleRoiPaymentModal');
    }

    public function save()
    {
        abort_unless(auth()->user()?->can('rent out roi.payment'), 403);
        $this->balance = RentOutRoi::totalsFor($this->rentOutId)['balance'];

        $this->validate([
            'form.date' => 'required|date',
            'form.amount' => 'required|numeric|min:0.01|max:'.max($this->balance, 0),
            'form.account_id' => 'required',
        ], [
            'form.date.required' => 'Date is required.',
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
            'form.amount.max' => 'Amount cannot exceed the ROI balance of '.number_format($this->balance, 2).'.',
            'form.account_id.required' => 'Payment method is required.',
        ]);

        try {
            DB::beginTransaction();

            $response = RentOutTransactionHelper::storeRoiPayment(
                $this->rentOutId,
                $this->form['date'],
                (float) $this->form['amount'],
                (int) $this->form['account_id'],
                $this->form['remark'] ?? ''
            );

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('ToggleRoiPaymentModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'ROI paid successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.roi-payment-modal', [
            'paymentMethods' => paymentMethodsOptions(),
        ]);
    }
}
