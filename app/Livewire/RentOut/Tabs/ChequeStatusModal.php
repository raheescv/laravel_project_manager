<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Cheque\UpdateStatusAction;
use App\Enums\RentOut\ChequeStatus;
use App\Models\RentOutCheque;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ChequeStatusModal extends Component
{
    public array $selected = [];

    public $statusChangeStatus = '';

    public $statusChangePaymentMethod = '';

    public $statusChangeJournalDate = '';

    public $statusChangeRemark = '';

    #[On('open-cheque-status-modal')]
    public function openModal(array $selectedIds): void
    {
        $this->selected = $selectedIds;
        $this->statusChangeStatus = ChequeStatus::Cleared->value;
        $this->statusChangePaymentMethod = '';
        $this->statusChangeJournalDate = now()->format('Y-m-d');
        $this->statusChangeRemark = '';
        $this->dispatch('ToggleChequeStatusModal');
    }

    public function updateChequeStatus(): void
    {
        $this->validate([
            'statusChangeStatus' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $unmatchedCheques = [];

            foreach ($this->selected as $id) {
                $cheque = RentOutCheque::findOrFail($id);
                $response = (new UpdateStatusAction())->execute($cheque, [
                    'status' => $this->statusChangeStatus,
                    'payment_method' => $this->statusChangePaymentMethod,
                    'journal_date' => $this->statusChangeJournalDate,
                    'remark' => $this->statusChangeRemark,
                ]);

                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }

                // Collect cheques that need term selection
                if (! empty($response['has_unpaid_terms'])) {
                    $unmatchedCheques[] = [
                        'id' => $cheque->id,
                        'cheque_no' => $cheque->cheque_no,
                        'amount' => $cheque->amount,
                        'date' => $cheque->date?->format('d-m-Y'),
                        'customer' => $cheque->rentOut?->customer?->name ?? '',
                        'available_terms' => $response['available_terms'],
                    ];
                }
            }

            DB::commit();

            if (! empty($unmatchedCheques)) {
                // Close status modal and open term selector
                $this->dispatch('ToggleChequeStatusModal');
                $this->dispatch('open-cheque-term-selector-modal', [
                    'pendingCheques' => $unmatchedCheques,
                    'statusChangePaymentMethod' => $this->statusChangePaymentMethod,
                    'statusChangeJournalDate' => $this->statusChangeJournalDate,
                    'statusChangeRemark' => $this->statusChangeRemark,
                ]);
            } else {
                $this->dispatch('success', ['message' => 'Successfully updated '.count($this->selected).' cheque(s).']);
                $this->dispatch('ToggleChequeStatusModal');
                $this->dispatch('cheque-table-refresh');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Get selected cheques info for the modal display.
     */
    public function getSelectedChequesProperty(): array
    {
        if (empty($this->selected)) {
            return [];
        }

        return RentOutCheque::with(['rentOut.customer'])
            ->whereIn('id', $this->selected)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'customer' => $c->rentOut?->customer?->name,
                'cheque_no' => $c->cheque_no,
                'amount' => $c->amount,
                'date' => $c->date?->format('d-m-Y'),
                'rent_out_id' => $c->rent_out_id,
                'agreement_type' => $c->rentOut?->agreement_type?->value ?? 'rental',
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.cheque-status-modal', [
            'chequeStatuses' => ChequeStatus::cases(),
        ]);
    }
}
