<?php

namespace App\Livewire\RentOut\Concerns;

use App\Actions\RentOut\PaymentTerm\CreateAction;
use App\Actions\RentOut\PaymentTerm\DeleteAction;
use App\Actions\RentOut\PaymentTerm\UpdateAction;
use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

trait HasPaymentTermManagement
{
    public $rentOut;

    // Note
    public $newNote = '';

    /**
     * Override in child class to set the default label for multiple terms.
     * e.g. 'rent payment' for rental, 'installment' for sale.
     */
    protected function defaultTermLabel(): string
    {
        return 'rent payment';
    }

    public function loadRentOut($id = null)
    {
        $this->rentOut = RentOut::with([
            'customer',
            'property',
            'building',
            'group',
            'type',
            'salesman',
            'paymentTerms',
            'securities',
            'cheques',
            'extends',
            'notes.creator',
            'services',
            'utilities',
            'utilityTerms.utility',
            'journals',
        ])->find($id ?? $this->rentOut->id);
    }

    // ─── Single Term ────────────────────────────────────────────

    public function openSingleTermModal()
    {
        $this->dispatch('open-single-term-modal',
            form: [
                'rent_out_id' => $this->rentOut->id,
                'due_date' => now()->format('Y-m-d'),
                'label' => '',
                'amount' => $this->rentOut->rent ?? 0,
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

    #[On('saveSingleTermFromModal')]
    public function saveSingleTermFromModal($form, $editingTermId = null)
    {
        $form['rent_out_id'] = $this->rentOut->id;

        try {
            DB::beginTransaction();
            if ($editingTermId) {
                $response = (new UpdateAction())->execute($form, $editingTermId);
            } else {
                $response = (new CreateAction())->execute($form);
            }
            if (! $response['success']) {
                throw new \Exception($response['message']);
            }
            DB::commit();
            $this->loadRentOut();
            $this->dispatch('single-term-saved');
            $this->dispatch('success', message: $response['message']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    // ─── Multiple Term ──────────────────────────────────────────

    public function openMultipleTermModal()
    {
        $noOfTerms = $this->rentOut->no_of_terms ?? 12;
        $rent = $this->rentOut->rent ?? 0;

        $lastTerm = $this->rentOut->paymentTerms->sortByDesc('due_date')->first();
        if ($lastTerm) {
            $fromDate = date('Y-m-d', strtotime('+1 month', strtotime($lastTerm->due_date)));
        } else {
            $day = str_pad($this->rentOut->collection_starting_day ?? 1, 2, '0', STR_PAD_LEFT);
            $fromDate = date("Y-m-{$day}", strtotime($this->rentOut->start_date));
        }

        $this->dispatch('open-multiple-term-modal',
            fromDate: $fromDate,
            noOfTerms: $noOfTerms,
            rent: $rent,
            frequency: $this->rentOut->payment_frequency ?? 'Monthly',
            endDate: $this->rentOut->end_date?->format('Y-m-d'),
            info: [
                'rent' => $rent,
                'noOfTerms' => $noOfTerms,
                'frequency' => strtoupper($this->rentOut->payment_frequency ?? 'Monthly'),
                'startDate' => $this->rentOut->start_date?->format('d-m-Y'),
                'endDate' => $this->rentOut->end_date?->format('d-m-Y'),
            ],
        );
    }

    #[On('saveMultipleTermsFromModal')]
    public function saveMultipleTermsFromModal($terms)
    {
        try {
            DB::beginTransaction();
            if (! count($terms)) {
                throw new \Exception('No terms to create.');
            }
            foreach ($terms as $item) {
                $data = [
                    'rent_out_id' => $this->rentOut->id,
                    'due_date' => $item['date'],
                    'label' => $this->defaultTermLabel(),
                    'amount' => $item['rent'],
                    'discount' => $item['discount'] ?? 0,
                ];
                $response = (new CreateAction())->execute($data);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }
            DB::commit();
            $this->loadRentOut();
            $this->dispatch('multiple-terms-saved');
            $this->dispatch('success', message: 'Successfully created ' . count($terms) . ' payment terms.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    // ─── Delete Payment Terms ───────────────────────────────────

    #[On('deleteSelectedTermsFromJS')]
    public function deleteSelectedTermsFromJS($ids)
    {
        $this->deleteSelectedTerms($ids);
    }

    #[On('paySelectedTermsFromJS')]
    public function paySelectedTermsFromJS($ids)
    {
        $this->openPaySelectedModal($ids);
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
            $this->loadRentOut();
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
            $this->loadRentOut();
            $this->dispatch('success', message: 'Successfully deleted ' . count($ids) . ' payment term(s).');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    // ─── Pay Selected ───────────────────────────────────────────

    public function openPaySelectedModal($ids)
    {
        $terms = RentOutPaymentTerm::whereIn('id', $ids)->where('balance', '>', 0)->get();
        $cashTerms = $terms->map(function ($term) {
            return [
                'id' => $term->id,
                'date' => $term->due_date?->format('d-m-Y'),
                'customer' => $this->rentOut->customer?->name ?? '',
                'property' => $this->rentOut->property?->number ?? '',
                'balance' => $term->balance,
                'amount' => $term->balance,
                'payment_mode' => 'cash',
                'remark' => '',
            ];
        })->toArray();

        $this->dispatch('open-pay-selected-modal',
            payDate: now()->format('Y-m-d'),
            payPaymentMode: 'cash',
            cashTerms: $cashTerms,
        );
    }

    #[On('submitPaymentFromModal')]
    public function submitPaymentFromModal($payDate, $payPaymentMode, $payRemark, $cashTerms)
    {
        try {
            DB::beginTransaction();
            foreach ($cashTerms as $cashTerm) {
                $term = RentOutPaymentTerm::find($cashTerm['id']);
                if ($term && $cashTerm['amount'] > 0) {
                    $term->paid = ($term->paid ?? 0) + $cashTerm['amount'];
                    $term->payment_mode = $cashTerm['payment_mode'] ?? $payPaymentMode;
                    $term->paid_date = $payDate;
                    $term->save();
                }
            }
            DB::commit();
            $this->loadRentOut();
            $this->dispatch('payment-submitted');
            $this->dispatch('success', message: 'Payment submitted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    // ─── Notes ──────────────────────────────────────────────────

    public function addNote()
    {
        if (trim($this->newNote) === '') {
            return;
        }

        try {
            DB::beginTransaction();
            $this->rentOut->notes()->create([
                'tenant_id' => $this->rentOut->tenant_id,
                'branch_id' => $this->rentOut->branch_id,
                'note' => $this->newNote,
                'created_by' => Auth::id(),
            ]);
            DB::commit();
            $this->newNote = '';
            $this->loadRentOut();
            $this->dispatch('success', message: 'Note added successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function deleteNote($id)
    {
        try {
            DB::beginTransaction();
            $note = $this->rentOut->notes()->find($id);
            if ($note) {
                $note->delete();
            }
            DB::commit();
            $this->loadRentOut();
            $this->dispatch('success', message: 'Note deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }
}
