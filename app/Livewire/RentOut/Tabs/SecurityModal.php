<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Security\CreateAction;
use App\Actions\RentOut\Security\UpdateAction;
use App\Enums\RentOut\SecurityStatus;
use App\Enums\RentOut\SecurityType;
use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class SecurityModal extends Component
{
    public ?int $editingId = null;

    public array $form = [
        'amount' => 0,
        'account_id' => '',
        'bank_name' => '',
        'cheque_no' => '',
        'type' => '',
        'status' => '',
        'due_date' => '',
        'collected_date' => '',
        'returned_date' => '',
        'remarks' => '',
    ];

    public ?int $rentOutId = null;

    #[On('open-security-modal')]
    public function openModal($form = [], $editingId = null)
    {
        $this->form = [
            'amount' => $form['amount'] ?? 0,
            'account_id' => $form['account_id'] ?? '',
            'bank_name' => $form['bank_name'] ?? '',
            'cheque_no' => $form['cheque_no'] ?? '',
            'type' => $form['type'] ?? '',
            'status' => $form['status'] ?? '',
            'due_date' => $form['due_date'] ?? now()->format('Y-m-d'),
            'collected_date' => $form['collected_date'] ?? '',
            'returned_date' => $form['returned_date'] ?? '',
            'remarks' => $form['remarks'] ?? '',
        ];
        $this->rentOutId = $form['rent_out_id'] ?? null;
        $this->editingId = $editingId;
        $this->resetValidation();
        $this->dispatch('ToggleSecurityModal');
    }

    public function save()
    {
        abort_unless(auth()->user()?->can($this->editingId ? 'rent out security.edit' : 'rent out security.create'), 403);
        $rules = [
            'form.amount' => 'required|numeric|min:0.01',
            'form.account_id' => 'nullable',
            'form.type' => 'required',
            'form.status' => 'required',
            'form.due_date' => 'nullable|date',
        ];

        $messages = [
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
            'form.account_id.required' => 'Payment method is required.',
            'form.type.required' => 'Type is required.',
            'form.status.required' => 'Status is required.',
        ];

        $isCheque = $this->isChequeSelected();
        if ($isCheque) {
            $rules['form.bank_name'] = 'required|string|max:255';
            $rules['form.cheque_no'] = 'required|string|max:255';
            $messages['form.bank_name.required'] = 'Bank name is required for cheque payments.';
            $messages['form.cheque_no.required'] = 'Cheque number is required for cheque payments.';
        }

        // Payment method and collection date are needed once the deposit money
        // is received (that is what posts the ledger); return date only once
        // it is handed back.
        if ($this->requiresPayment()) {
            $rules['form.account_id'] = 'required';
            $rules['form.collected_date'] = 'required|date';
            $messages['form.collected_date.required'] = 'Collected date is required for this status.';
        }
        if (in_array($this->form['status'], SecurityStatus::refundedValues(), true)) {
            $rules['form.returned_date'] = 'required|date';
            $messages['form.returned_date.required'] = 'Returned date is required when the deposit is returned.';
        }

        $this->validate($rules, $messages);

        $data = [
            'rent_out_id' => $this->rentOutId,
            'amount' => $this->form['amount'],
            'account_id' => $this->form['account_id'] ?: null,
            'bank_name' => $isCheque ? ($this->form['bank_name'] ?? '') : null,
            'cheque_no' => $isCheque ? ($this->form['cheque_no'] ?? '') : null,
            'type' => $this->form['type'],
            'status' => $this->form['status'],
            'due_date' => $this->form['due_date'] ?: null,
            'collected_date' => $this->form['collected_date'] ?: null,
            'returned_date' => $this->form['returned_date'] ?: null,
            'remarks' => $this->form['remarks'] ?? '',
        ];
        if (! $data['account_id']) {
            $data['payment_mode'] = null;
        }

        try {
            DB::beginTransaction();
            if ($this->editingId) {
                $response = (new UpdateAction())->execute($data, $this->editingId);
            } else {
                $response = (new CreateAction())->execute($data);
            }
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->dispatch('ToggleSecurityModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    /**
     * Payment-method account ids flagged as cheque accounts (as strings, to
     * match the select value).
     */
    protected function chequeAccountIds(): array
    {
        return Account::whereIn('id', array_keys(paymentMethodsOptions()))
            ->where('is_cheque', 1)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    /**
     * Whether the selected status records the deposit money as received, so a
     * payment method is needed to post the ledger.
     */
    public function requiresPayment(): bool
    {
        return in_array($this->form['status'], SecurityStatus::collectedValues(), true);
    }

    public function isChequeSelected(): bool
    {
        return in_array((string) $this->form['account_id'], $this->chequeAccountIds(), true);
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.security-modal', [
            'paymentMethods' => paymentMethodsOptions(),
            'chequeAccountIds' => $this->chequeAccountIds(),
            'securityTypes' => SecurityType::cases(),
            'securityStatuses' => SecurityStatus::cases(),
            'collectedStatuses' => SecurityStatus::collectedValues(),
            'refundedStatuses' => SecurityStatus::refundedValues(),
        ]);
    }
}
