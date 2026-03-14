<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Cheque\DeleteAction;
use App\Actions\RentOut\Cheque\UpdateStatusAction;
use App\Models\RentOut;
use App\Models\RentOutCheque;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ChequesTab extends Component
{
    public $rentOutId;
    public $sortField = 'date';
    public $sortDirection = 'asc';
    public array $selectedCheques = [];
    public bool $selectAll = false;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[On('rent-out-updated')]
    public function refresh() {}

    public function openSingleChequeModal()
    {
        $rentOut = RentOut::with('customer')->find($this->rentOutId);

        $this->dispatch('open-single-cheque-modal',
            form: [
                'rent_out_id' => $rentOut->id,
                'cheque_no' => '',
                'bank_name' => '',
                'amount' => $rentOut->rent ?? 0,
                'date' => now()->format('Y-m-d'),
                'payee_name' => $rentOut->customer?->name ?? '',
                'status' => 'uncleared',
                'remarks' => '',
            ],
            editingId: null,
        );
    }

    public function editCheque($id)
    {
        $cheque = RentOutCheque::find($id);
        if (! $cheque) {
            return;
        }

        $this->dispatch('open-single-cheque-modal',
            form: [
                'rent_out_id' => $cheque->rent_out_id,
                'cheque_no' => $cheque->cheque_no ?? '',
                'bank_name' => $cheque->bank_name ?? '',
                'amount' => $cheque->amount,
                'date' => $cheque->date?->format('Y-m-d') ?? '',
                'payee_name' => $cheque->payee_name ?? '',
                'status' => $cheque->status?->value ?? 'uncleared',
                'remarks' => $cheque->remarks ?? '',
            ],
            editingId: $id,
        );
    }

    public function openMultipleChequeModal()
    {
        $rentOut = RentOut::with('customer')->find($this->rentOutId);

        $this->dispatch('open-multiple-cheque-modal',
            rentOutId: $rentOut->id,
            startNo: '',
            bankName: $rentOut->collection_bank_name ?? '',
            amount: $rentOut->rent ?? 0,
            startDate: now()->format('Y-m-d'),
            count: $rentOut->no_of_terms ?? 12,
            frequency: $rentOut->payment_frequency ?? 'Monthly',
            payeeName: $rentOut->customer?->name ?? '',
        );
    }

    public function updateStatus($id, $status)
    {
        try {
            DB::beginTransaction();
            $response = (new UpdateStatusAction())->execute($id, $status);
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

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedCheques = RentOutCheque::where('rent_out_id', $this->rentOutId)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedCheques = [];
        }
    }

    public function deleteSelected()
    {
        if (empty($this->selectedCheques)) {
            $this->dispatch('error', message: 'No cheques selected.');
            return;
        }

        try {
            DB::beginTransaction();
            foreach ($this->selectedCheques as $id) {
                $response = (new DeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }
            DB::commit();
            $count = count($this->selectedCheques);
            $this->selectedCheques = [];
            $this->selectAll = false;
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: "Successfully deleted {$count} cheque(s).");
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $rentOut = RentOut::with(['cheques' => function ($query) {
            $query->orderBy($this->sortField, $this->sortDirection);
        }])->find($this->rentOutId);

        return view('livewire.rent-out.tabs.cheques-tab', [
            'rentOut' => $rentOut,
            'chequeStatuses' => \App\Enums\RentOut\ChequeStatus::cases(),
        ]);
    }
}
