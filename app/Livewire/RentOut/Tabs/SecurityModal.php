<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Security\CreateAction;
use App\Actions\RentOut\Security\UpdateAction;
use App\Enums\RentOut\PaymentMode;
use App\Enums\RentOut\SecurityStatus;
use App\Enums\RentOut\SecurityType;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class SecurityModal extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public array $form = [
        'amount' => 0,
        'payment_mode' => '',
        'bank_name' => '',
        'cheque_no' => '',
        'type' => '',
        'status' => '',
        'due_date' => '',
        'remarks' => '',
    ];

    public ?int $rentOutId = null;

    #[On('open-security-modal')]
    public function openModal($form = [], $editingId = null)
    {
        $this->form = [
            'amount' => $form['amount'] ?? 0,
            'payment_mode' => $form['payment_mode'] ?? '',
            'bank_name' => $form['bank_name'] ?? '',
            'cheque_no' => $form['cheque_no'] ?? '',
            'type' => $form['type'] ?? '',
            'status' => $form['status'] ?? '',
            'due_date' => $form['due_date'] ?? now()->format('Y-m-d'),
            'remarks' => $form['remarks'] ?? '',
        ];
        $this->rentOutId = $form['rent_out_id'] ?? null;
        $this->editingId = $editingId;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $rules = [
            'form.amount' => 'required|numeric|min:0.01',
            'form.payment_mode' => 'required',
            'form.type' => 'required',
            'form.status' => 'required',
            'form.due_date' => 'required|date',
        ];

        $messages = [
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
            'form.payment_mode.required' => 'Payment mode is required.',
            'form.type.required' => 'Type is required.',
            'form.status.required' => 'Status is required.',
            'form.due_date.required' => 'Due date is required.',
        ];

        if ($this->form['payment_mode'] === 'cheque') {
            $rules['form.bank_name'] = 'required|string|max:255';
            $rules['form.cheque_no'] = 'required|string|max:255';
            $messages['form.bank_name.required'] = 'Bank name is required for cheque payments.';
            $messages['form.cheque_no.required'] = 'Cheque number is required for cheque payments.';
        }

        $this->validate($rules, $messages);

        $data = [
            'rent_out_id' => $this->rentOutId,
            'amount' => $this->form['amount'],
            'payment_mode' => $this->form['payment_mode'],
            'bank_name' => $this->form['payment_mode'] === 'cheque' ? ($this->form['bank_name'] ?? '') : null,
            'cheque_no' => $this->form['payment_mode'] === 'cheque' ? ($this->form['cheque_no'] ?? '') : null,
            'type' => $this->form['type'],
            'status' => $this->form['status'],
            'due_date' => $this->form['due_date'],
            'remarks' => $this->form['remarks'] ?? '',
        ];

        try {
            DB::beginTransaction();
            if ($this->editingId) {
                $response = (new UpdateAction)->execute($data, $this->editingId);
            } else {
                $response = (new CreateAction)->execute($data);
            }
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
        return view('livewire.rent-out.tabs.security-modal', [
            'paymentModes' => PaymentMode::cases(),
            'securityTypes' => SecurityType::cases(),
            'securityStatuses' => SecurityStatus::cases(),
        ]);
    }
}
