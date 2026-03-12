<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Cheque\CreateAction;
use App\Enums\RentOut\ChequeStatus;
use App\Models\RentOut;
use App\Models\RentOutCheque;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class SingleChequeModal extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public array $form = [
        'rent_out_id' => null,
        'cheque_no' => '',
        'bank_name' => '',
        'amount' => 0,
        'date' => '',
        'payee_name' => '',
        'status' => 'uncleared',
        'remarks' => '',
    ];

    #[On('open-single-cheque-modal')]
    public function openModal($form = [], $editingId = null)
    {
        $this->form = [
            'rent_out_id' => $form['rent_out_id'] ?? null,
            'cheque_no' => $form['cheque_no'] ?? '',
            'bank_name' => $form['bank_name'] ?? '',
            'amount' => $form['amount'] ?? 0,
            'date' => $form['date'] ?? now()->format('Y-m-d'),
            'payee_name' => $form['payee_name'] ?? '',
            'status' => $form['status'] ?? 'uncleared',
            'remarks' => $form['remarks'] ?? '',
        ];
        $this->editingId = $editingId;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'form.cheque_no' => 'required|string',
            'form.amount' => 'required|numeric|min:0.01',
        ], [
            'form.cheque_no.required' => 'Cheque No is required.',
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
        ]);

        try {
            DB::beginTransaction();

            if ($this->editingId) {
                $cheque = RentOutCheque::findOrFail($this->editingId);
                $cheque->update([
                    'cheque_no' => $this->form['cheque_no'],
                    'bank_name' => $this->form['bank_name'],
                    'amount' => $this->form['amount'],
                    'date' => $this->form['date'],
                    'payee_name' => $this->form['payee_name'],
                    'status' => $this->form['status'],
                    'remarks' => $this->form['remarks'],
                ]);
                $message = 'Successfully Updated Cheque';
            } else {
                $rentOut = RentOut::findOrFail($this->form['rent_out_id']);
                $data = [
                    'rent_out_id' => $this->form['rent_out_id'],
                    'cheque_no' => $this->form['cheque_no'],
                    'bank_name' => $this->form['bank_name'],
                    'amount' => $this->form['amount'],
                    'date' => $this->form['date'],
                    'payee_name' => $this->form['payee_name'],
                    'status' => $this->form['status'],
                    'remarks' => $this->form['remarks'],
                    'tenant_id' => $rentOut->tenant_id,
                    'branch_id' => $rentOut->branch_id,
                    'created_by' => $rentOut->created_by,
                ];
                $response = (new CreateAction())->execute($data);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
                $message = $response['message'];
            }

            DB::commit();
            $this->showModal = false;
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $message);
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
        return view('livewire.rent-out.tabs.single-cheque-modal', [
            'statusOptions' => ChequeStatus::cases(),
        ]);
    }
}
