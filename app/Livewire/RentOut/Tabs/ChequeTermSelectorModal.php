<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Cheque\UpdateStatusAction;
use App\Models\RentOutCheque;
use App\Models\RentOutPaymentTerm;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ChequeTermSelectorModal extends Component
{
    public array $pendingCheques = [];

    public array $availableTerms = [];

    public $selectedTermId = null;

    public $statusChangePaymentMethod = '';

    public $statusChangeJournalDate = '';

    public $statusChangeRemark = '';

    #[On('open-cheque-term-selector-modal')]
    public function openModal(array $data): void
    {
        $this->pendingCheques = $data['pendingCheques'];
        $this->availableTerms = $data['pendingCheques'][0]['available_terms'] ?? [];
        $this->selectedTermId = null;
        $this->statusChangePaymentMethod = $data['statusChangePaymentMethod'] ?? '';
        $this->statusChangeJournalDate = $data['statusChangeJournalDate'] ?? '';
        $this->statusChangeRemark = $data['statusChangeRemark'] ?? '';
        $this->dispatch('ToggleChequeTermSelectorModal');
    }

    public function confirmTermPayment(): void
    {
        if (! $this->selectedTermId || empty($this->pendingCheques)) {
            $this->dispatch('error', ['message' => 'Please select a payment term.']);

            return;
        }

        try {
            DB::beginTransaction();

            $action = new UpdateStatusAction();
            $term = RentOutPaymentTerm::findOrFail($this->selectedTermId);

            foreach ($this->pendingCheques as $pendingCheque) {
                $cheque = RentOutCheque::find($pendingCheque['id']);
                if ($cheque) {
                    $action->payTermWithCheque(
                        $pendingCheque['id'],
                        $term,
                        $this->statusChangePaymentMethod ?: null,
                        $this->statusChangeJournalDate ?: null,
                        $this->statusChangeRemark ?: null,
                    );
                }
            }

            DB::commit();
            $this->dispatch('success', ['message' => 'Cheque(s) cleared and payment term paid successfully.']);
            $this->dispatch('ToggleChequeTermSelectorModal');
            $this->dispatch('cheque-table-refresh');
            $this->resetState();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function skipTermPayment(): void
    {
        $this->dispatch('success', ['message' => 'Cheque status updated without payment.']);
        $this->dispatch('ToggleChequeTermSelectorModal');
        $this->dispatch('cheque-table-refresh');
        $this->resetState();
    }

    protected function resetState(): void
    {
        $this->pendingCheques = [];
        $this->availableTerms = [];
        $this->selectedTermId = null;
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.cheque-term-selector-modal');
    }
}
