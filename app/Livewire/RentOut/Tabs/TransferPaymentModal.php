<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Payment\TransferTransactionAction;
use App\Enums\RentOut\AgreementType;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use App\Models\RentOutTransaction;
use Livewire\Attributes\On;
use Livewire\Component;

class TransferPaymentModal extends Component
{
    public ?int $paymentId = null;

    public ?int $fromRentOutId = null;

    // What is still left on the receipt to transfer (its amount minus earlier transfers).
    public float $available = 0;

    // Search term for the (searchable) target-property picker.
    public string $search = '';

    // Human-readable label of the currently selected target property.
    public ?string $selectedTargetLabel = null;

    public array $form = [
        'to_rent_out_id' => '',
        'to_term_id' => '',
        'amount' => '',
        'remark' => '',
    ];

    #[On('open-transfer-modal')]
    public function openModal($paymentId)
    {
        $payment = RentOutTransaction::findOrFail($paymentId);
        $fromRentOut = RentOut::findOrFail($payment->rent_out_id);

        $this->paymentId = $payment->id;
        $this->fromRentOutId = $fromRentOut->id;
        $this->available = TransferTransactionAction::transferableAmount($payment);

        $this->search = '';
        $this->selectedTargetLabel = null;
        $this->form = [
            'to_rent_out_id' => '',
            'to_term_id' => '',
            'amount' => $this->available,
            'remark' => '',
        ];

        $this->resetValidation();
        $this->dispatch('ToggleTransferPaymentModal');
    }

    public function selectTarget(int $rentOutId, string $label)
    {
        $this->form['to_rent_out_id'] = $rentOutId;
        $this->form['to_term_id'] = '';
        $this->selectedTargetLabel = $label;
        $this->search = '';
    }

    public function clearTarget()
    {
        $this->form['to_rent_out_id'] = '';
        $this->form['to_term_id'] = '';
        $this->selectedTargetLabel = null;
    }

    public function transfer()
    {
        $fromRentOut = RentOut::find($this->fromRentOutId);
        abort_unless(auth()->user()?->can($fromRentOut?->agreement_type === AgreementType::Lease ? 'rent out lease.transfer payment' : 'rent out.transfer payment'), 403);

        $this->validate([
            'form.to_rent_out_id' => 'required',
            'form.amount' => 'required|numeric|min:0.01|max:'.$this->available,
        ], [
            'form.to_rent_out_id.required' => 'Select the property to transfer to.',
            'form.amount.required' => 'Enter the amount to transfer.',
            'form.amount.numeric' => 'The amount must be a number.',
            'form.amount.min' => 'The amount must be greater than zero.',
            'form.amount.max' => 'The amount cannot exceed the '.number_format($this->available, 2).' left on this payment.',
        ]);

        $response = (new TransferTransactionAction())->execute(
            $this->paymentId,
            (int) $this->form['to_rent_out_id'],
            $this->form['to_term_id'] ? (int) $this->form['to_term_id'] : null,
            $this->form['remark'] ?? '',
            (float) $this->form['amount'],
        );

        if (! $response['success']) {
            $this->dispatch('error', message: $response['message']);

            return;
        }

        $this->dispatch('ToggleTransferPaymentModal');
        $this->dispatch('rent-out-updated');
        $this->dispatch('success', message: $response['message']);
    }

    public function render()
    {
        // Candidate targets: any other agreement in this tenant (auto-scoped),
        // whichever customer holds it, matching the search term.
        $targets = collect();
        if (! $this->form['to_rent_out_id']) {
            $targets = RentOut::query()
                ->where('id', '!=', $this->fromRentOutId)
                ->when($this->search !== '', function ($q) {
                    $term = trim($this->search);
                    $q->where(function ($sub) use ($term) {
                        if (ctype_digit($term)) {
                            $sub->orWhere('id', (int) $term);
                        }
                        $sub->orWhereHas('property', function ($p) use ($term) {
                            $p->where('number', 'like', "%{$term}%")
                                ->orWhere('code', 'like', "%{$term}%")
                                ->orWhere('unit_no', 'like', "%{$term}%");
                        })->orWhereHas('customer', function ($c) use ($term) {
                            $c->where('name', 'like', "%{$term}%");
                        });
                    });
                })
                ->with(['property', 'group', 'building', 'type', 'customer'])
                ->orderByDesc('id')
                ->limit(10)
                ->get();
        }

        $terms = collect();
        $selectedTargetUrl = null;
        if ($this->form['to_rent_out_id']) {
            $selectedTarget = RentOut::find($this->form['to_rent_out_id']);
            if ($selectedTarget) {
                $selectedTargetUrl = route($selectedTarget->agreement_type === AgreementType::Lease ? 'property::sale::view' : 'property::rent::view', $selectedTarget->id);
            }

            $terms = RentOutPaymentTerm::query()
                ->where('rent_out_id', $this->form['to_rent_out_id'])
                ->where('status', '!=', 'paid')
                ->orderBy('due_date')
                ->get();
        }

        return view('livewire.rent-out.tabs.transfer-payment-modal', [
            'targets' => $targets,
            'terms' => $terms,
            'selectedTargetUrl' => $selectedTargetUrl,
        ]);
    }
}
