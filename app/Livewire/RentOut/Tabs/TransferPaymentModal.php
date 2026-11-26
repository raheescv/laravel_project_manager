<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Payment\TransferTransactionAction;
use App\Enums\RentOut\AgreementType;
use App\Models\RentOut;
use Livewire\Attributes\On;
use Livewire\Component;

class TransferPaymentModal extends Component
{
    public ?int $fromRentOutId = null;

    // Search term for the (searchable) target-property picker.
    public string $search = '';

    // Human-readable label of the currently selected target property.
    public ?string $selectedTargetLabel = null;

    public array $form = [
        'to_rent_out_id' => '',
        'date' => '',
        'amount' => '',
        'remark' => '',
    ];

    #[On('open-transfer-modal')]
    public function openModal($rentOutId)
    {
        $this->fromRentOutId = RentOut::findOrFail($rentOutId)->id;

        $this->search = '';
        $this->selectedTargetLabel = null;
        $this->form = [
            'to_rent_out_id' => '',
                'date' => now()->format('Y-m-d'),
            'amount' => '',
            'remark' => '',
        ];

        $this->resetValidation();
        $this->dispatch('ToggleTransferPaymentModal');
    }

    public function selectTarget(int $rentOutId, string $label)
    {
        $this->form['to_rent_out_id'] = $rentOutId;
        $this->selectedTargetLabel = $label;
        $this->search = '';
    }

    public function clearTarget()
    {
        $this->form['to_rent_out_id'] = '';
        $this->selectedTargetLabel = null;
    }

    public function transfer()
    {
        $fromRentOut = RentOut::find($this->fromRentOutId);
        abort_unless(auth()->user()?->can($fromRentOut?->agreement_type === AgreementType::Lease ? 'rent out lease.transfer payment' : 'rent out.transfer payment'), 403);

        $this->validate([
            'form.to_rent_out_id' => 'required',
            'form.date' => 'required|date',
            'form.amount' => 'required|numeric|min:0.01',
        ], [
            'form.to_rent_out_id.required' => 'Select the property to transfer to.',
            'form.date.required' => 'Date is required.',
            'form.amount.required' => 'Enter the amount to transfer.',
            'form.amount.numeric' => 'The amount must be a number.',
            'form.amount.min' => 'The amount must be greater than zero.',
        ]);

        $response = (new TransferTransactionAction())->execute(
            $this->fromRentOutId,
            (int) $this->form['to_rent_out_id'],
            (float) $this->form['amount'],
            null,
            $this->form['remark'] ?? '',
            $this->form['date'],
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

        $selectedTargetUrl = null;
        if ($this->form['to_rent_out_id']) {
            $selectedTarget = RentOut::find($this->form['to_rent_out_id']);
            if ($selectedTarget) {
                $selectedTargetUrl = route($selectedTarget->agreement_type === AgreementType::Lease ? 'property::sale::view' : 'property::rent::view', $selectedTarget->id);
            }
        }

        return view('livewire.rent-out.tabs.transfer-payment-modal', [
            'targets' => $targets,
            'selectedTargetUrl' => $selectedTargetUrl,
        ]);
    }
}
