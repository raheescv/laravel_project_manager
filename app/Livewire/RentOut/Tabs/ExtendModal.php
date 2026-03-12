<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\ExtendAction;
use App\Models\RentOut;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ExtendModal extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public array $form = [
        'rent_out_id' => null,
        'start_date' => '',
        'end_date' => '',
        'rent_amount' => 0,
        'payment_mode' => '',
        'remarks' => '',
    ];

    #[On('open-extend-modal')]
    public function openModal($form = [], $editingId = null)
    {
        $this->form = [
            'rent_out_id' => $form['rent_out_id'] ?? null,
            'start_date' => $form['start_date'] ?? now()->format('Y-m-d'),
            'end_date' => $form['end_date'] ?? '',
            'rent_amount' => $form['rent_amount'] ?? 0,
            'payment_mode' => $form['payment_mode'] ?? '',
            'remarks' => $form['remarks'] ?? '',
        ];
        $this->editingId = $editingId;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'form.start_date' => 'required|date',
            'form.end_date' => 'required|date|after_or_equal:form.start_date',
            'form.rent_amount' => 'required|numeric|min:0.01',
            'form.payment_mode' => 'required|string',
        ], [
            'form.start_date.required' => 'Start date is required.',
            'form.end_date.required' => 'End date is required.',
            'form.end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'form.rent_amount.required' => 'Rent amount is required.',
            'form.rent_amount.min' => 'Rent amount must be greater than zero.',
            'form.payment_mode.required' => 'Payment mode is required.',
        ]);

        $rentOut = RentOut::find($this->form['rent_out_id']);
        if (! $rentOut) {
            $this->dispatch('error', message: 'RentOut not found.');

            return;
        }

        $data = [
            'rent_out_id' => $this->form['rent_out_id'],
            'tenant_id' => $rentOut->tenant_id,
            'branch_id' => $rentOut->branch_id,
            'start_date' => $this->form['start_date'],
            'end_date' => $this->form['end_date'],
            'rent_amount' => $this->form['rent_amount'],
            'payment_mode' => $this->form['payment_mode'],
            'remarks' => $this->form['remarks'] ?? '',
        ];

        try {
            DB::beginTransaction();
            $response = (new ExtendAction)->execute($data);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->showModal = false;
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function close()
    {
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.extend-modal');
    }
}
