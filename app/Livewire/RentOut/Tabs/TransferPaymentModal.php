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

    // The source agreement's customer — transfers are restricted to this customer.
    public ?int $customerId = null;

    public ?string $customerName = null;

    // The full amount that will be moved (whole receipt — never partial).
    public float $amount = 0;

    // Search term for the (searchable) target-property picker.
    public string $search = '';

    // Human-readable label of the currently selected target property.
    public ?string $selectedTargetLabel = null;

    public array $form = [
        'to_rent_out_id' => '',
        'to_term_id' => '',
        'remark' => '',
    ];

    #[On('open-transfer-modal')]
    public function openModal($paymentId)
    {
        $payment = RentOutTransaction::findOrFail($paymentId);
        $fromRentOut = RentOut::with('customer')->findOrFail($payment->rent_out_id);

        $this->paymentId = $payment->id;
        $this->fromRentOutId = $fromRentOut->id;
        $this->customerId = $fromRentOut->account_id;
        $this->customerName = $fromRentOut->customer?->name;
        $this->amount = (float) $payment->credit;

        $this->search = '';
        $this->selectedTargetLabel = null;
        $this->form = [
            'to_rent_out_id' => '',
            'to_term_id' => '',
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
        ], [
            'form.to_rent_out_id.required' => 'Select the property to transfer to.',
        ]);

        $response = (new TransferTransactionAction())->execute(
            $this->paymentId,
            (int) $this->form['to_rent_out_id'],
            $this->form['to_term_id'] ? (int) $this->form['to_term_id'] : null,
            $this->form['remark'] ?? '',
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
        // Candidate targets: the SAME customer's other agreements (same tenant is
        // auto-scoped), matching the search term, excluding the source agreement.
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
                        });
                    });
                })
                ->with(['property', 'group', 'building', 'type'])
                ->orderByDesc('id')
                ->limit(10)
                ->get();
        }

        $terms = collect();
        if ($this->form['to_rent_out_id']) {
            $terms = RentOutPaymentTerm::query()
                ->where('rent_out_id', $this->form['to_rent_out_id'])
                ->where('status', '!=', 'paid')
                ->orderBy('due_date')
                ->get();
        }

        return view('livewire.rent-out.tabs.transfer-payment-modal', [
            'targets' => $targets,
            'terms' => $terms,
        ]);
    }
}
