<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\PaymentTerm\DeleteAction;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class PaymentTermsTab extends Component
{
    public $rentOutId;

    public $isRental = false;

    public $defaultLabel = 'rent payment';

    public $sortField = 'due_date';

    public $sortDirection = 'asc';

    public function mount($rentOutId, $isRental = false, $defaultLabel = 'rent payment')
    {
        $this->rentOutId = $rentOutId;
        $this->isRental = $isRental;
        $this->defaultLabel = $defaultLabel;
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
    public function refreshData()
    {
        // Forces Livewire to re-render with fresh data from getRentOut()
    }

    protected function getRentOut()
    {
        return RentOut::with(['customer', 'property', 'paymentTerms' => function ($query) {
            $query->orderBy($this->sortField, $this->sortDirection);
        }])->find($this->rentOutId);
    }

    // ─── Single Term ────────────────────────────────────────────

    public function openSingleTermModal()
    {
        $rentOut = $this->getRentOut();

        $this->dispatch('open-single-term-modal',
            form: [
                'rent_out_id' => $rentOut->id,
                'due_date' => now()->format('Y-m-d'),
                'label' => '',
                'amount' => $rentOut->rent ?? 0,
                'discount' => 0,
                'remarks' => '',
            ],
            editingTermId: null,
        );
    }

    public function editPaymentTerm($id)
    {
        $term = RentOutPaymentTerm::find($id);
        if ($term) {
            $this->dispatch('open-single-term-modal',
                form: [
                    'rent_out_id' => $term->rent_out_id,
                    'due_date' => $term->due_date?->format('Y-m-d'),
                    'label' => $term->label ?? '',
                    'amount' => $term->amount,
                    'discount' => $term->discount,
                    'remarks' => $term->remarks,
                ],
                editingTermId: $id,
            );
        }
    }

    // ─── Multiple Term ──────────────────────────────────────────

    public function openMultipleTermModal()
    {
        $rentOut = $this->getRentOut();
        $noOfTerms = $rentOut->no_of_terms ?? 12;
        $rent = $rentOut->rent ?? 0;

        $lastTerm = $rentOut->paymentTerms->sortByDesc('due_date')->first();
        if ($lastTerm) {
            $fromDate = date('Y-m-d', strtotime('+1 month', strtotime($lastTerm->due_date)));
        } else {
            $day = str_pad($rentOut->collection_starting_day ?? 1, 2, '0', STR_PAD_LEFT);
            $fromDate = date("Y-m-{$day}", strtotime($rentOut->start_date));
        }

        $this->dispatch('open-multiple-term-modal',
            fromDate: $fromDate,
            noOfTerms: $noOfTerms,
            rent: $rent,
            frequency: $rentOut->payment_frequency ?? 'Monthly',
            endDate: $rentOut->end_date?->format('Y-m-d'),
            info: [
                'rent' => $rent,
                'noOfTerms' => $noOfTerms,
                'frequency' => strtoupper($rentOut->payment_frequency ?? 'Monthly'),
                'startDate' => $rentOut->start_date?->format('d-m-Y'),
                'endDate' => $rentOut->end_date?->format('d-m-Y'),
            ],
            rentOutId: $this->rentOutId,
            defaultLabel: $this->defaultLabel,
        );
    }

    // ─── Delete Payment Terms ───────────────────────────────────

    #[On('deleteSelectedTermsFromJS')]
    public function deleteSelectedTermsFromJS($ids)
    {
        $this->deleteSelectedTerms($ids);
    }

    public function deletePaymentTerm($id)
    {
        try {
            DB::beginTransaction();
            $response = (new DeleteAction())->execute($id);
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

    public function deleteSelectedTerms($ids)
    {
        try {
            DB::beginTransaction();
            foreach ($ids as $id) {
                $response = (new DeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }
            DB::commit();
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Successfully deleted '.count($ids).' payment term(s).');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $rentOut = $this->getRentOut();

        return view('livewire.rent-out.tabs.payment-terms-tab', [
            'rentOut' => $rentOut,
            'isRental' => $this->isRental,
        ]);
    }
}
