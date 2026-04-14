<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\PaymentTerm\CreateAction;
use App\Actions\RentOut\PaymentTerm\UpdateAction;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class SinglePaymentTermModal extends Component
{
    public ?int $editingTermId = null;

    public array $form = [
        'due_date' => '',
        'label' => '',
        'amount' => 0,
        'discount' => 0,
        'remarks' => '',
    ];

    public ?int $rentOutId = null;

    #[On('open-single-term-modal')]
    public function openModal($form = [], $editingTermId = null)
    {
        $this->form = [
            'due_date' => $form['due_date'] ?? now()->format('Y-m-d'),
            'label' => $form['label'] ?? '',
            'amount' => $form['amount'] ?? 0,
            'discount' => $form['discount'] ?? 0,
            'remarks' => $form['remarks'] ?? '',
        ];
        $this->rentOutId = $form['rent_out_id'] ?? null;
        $this->editingTermId = $editingTermId;
        $this->resetValidation();
        $this->dispatch('ToggleSinglePaymentTermModal');
    }

    public function save()
    {
        $this->validate([
            'form.due_date' => 'required|date',
            'form.amount' => 'required|numeric|min:0.01',
        ], [
            'form.due_date.required' => 'Date is required.',
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
        ]);

        $data = [
            'rent_out_id' => $this->rentOutId,
            'due_date' => $this->form['due_date'],
            'label' => $this->form['label'],
            'amount' => $this->form['amount'],
            'discount' => $this->form['discount'] ?? 0,
            'remarks' => $this->form['remarks'] ?? '',
        ];

        try {
            DB::beginTransaction();
            if ($this->editingTermId) {
                $response = (new UpdateAction())->execute($data, $this->editingTermId);
            } else {
                $response = (new CreateAction())->execute($data);
            }
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->dispatch('ToggleSinglePaymentTermModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.single-payment-term-modal', [
            'labelOptions' => paymentTermLabels(),
        ]);
    }
}
