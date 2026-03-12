<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Utility\Term\DeleteAction as TermDeleteAction;
use App\Models\RentOut;
use App\Models\RentOutUtilityTerm;
use App\Models\Utility;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class UtilitiesTab extends Component
{
    public $rentOutId;

    public array $selectedTerms = [];

    public bool $selectAll = false;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    #[On('rent-out-updated')]
    public function refresh()
    {
        $this->selectedTerms = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedTerms = RentOutUtilityTerm::where('rent_out_id', $this->rentOutId)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedTerms = [];
        }
    }

    public function openUtilityTermModal()
    {
        $rentOut = RentOut::find($this->rentOutId);
        $utilities = Utility::orderBy('name')->get(['id', 'name']);

        $this->dispatch('open-utility-term-modal',
            form: [
                'rent_out_id' => $rentOut->id,
                'utility_id' => $utilities->first()?->id ?? '',
                'amount' => 0,
                'balance' => 0,
                'date' => now()->format('Y-m-d'),
                'from_date' => $rentOut->start_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'remarks' => '',
            ],
            editingId: null,
            utilities: $utilities->toArray(),
        );
    }

    public function editUtilityTerm($id)
    {
        $term = RentOutUtilityTerm::find($id);
        if (! $term) {
            return;
        }

        $utilities = Utility::orderBy('name')->get(['id', 'name']);

        $this->dispatch('open-utility-term-modal',
            form: [
                'rent_out_id' => $term->rent_out_id,
                'utility_id' => $term->utility_id,
                'amount' => $term->amount,
                'balance' => $term->balance,
                'date' => $term->date?->format('Y-m-d') ?? '',
                'remarks' => $term->remarks ?? '',
            ],
            editingId: $id,
            utilities: $utilities->toArray(),
        );
    }

    public function deleteUtilityTerm($id)
    {
        try {
            DB::beginTransaction();
            $response = (new TermDeleteAction())->execute($id);
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function deleteSelected()
    {
        if (empty($this->selectedTerms)) {
            $this->dispatch('error', message: 'Please select at least one row to delete.');

            return;
        }

        try {
            DB::beginTransaction();
            $action = new TermDeleteAction();
            foreach ($this->selectedTerms as $id) {
                $response = $action->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }
            DB::commit();
            $this->selectedTerms = [];
            $this->selectAll = false;
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Selected utility terms deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function paySelected()
    {
        if (empty($this->selectedTerms)) {
            $this->dispatch('error', message: 'Please select at least one row for payment.');

            return;
        }

        $this->dispatch('open-utility-pay-selected-modal', ids: array_map('intval', $this->selectedTerms));
    }

    public function render()
    {
        $rentOut = RentOut::with(['utilityTerms.utility'])->find($this->rentOutId);

        return view('livewire.rent-out.tabs.utilities-tab', [
            'rentOut' => $rentOut,
        ]);
    }
}
