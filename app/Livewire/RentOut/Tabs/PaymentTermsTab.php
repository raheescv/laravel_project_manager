<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\PaymentTerm\CreateAction;
use App\Actions\RentOut\PaymentTerm\DeleteAction;
use App\Actions\RentOut\PaymentTerm\UpdateAction;
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

    public function mount($rentOutId, $isRental = false, $defaultLabel = 'rent payment')
    {
        $this->rentOutId = $rentOutId;
        $this->isRental = $isRental;
        $this->defaultLabel = $defaultLabel;
    }

    protected function getRentOut()
    {
        return RentOut::with(['customer', 'property', 'paymentTerms'])->find($this->rentOutId);
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

    #[On('saveSingleTermFromModal')]
    public function saveSingleTermFromModal($form, $editingTermId = null)
    {
        $form['rent_out_id'] = $this->rentOutId;

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
            $this->dispatch('single-term-saved');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
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
                    'rent_out_id' => $this->rentOutId,
                    'due_date' => $item['date'],
                    'label' => $this->defaultLabel,
                    'amount' => $item['rent'],
                    'discount' => $item['discount'] ?? 0,
                ];
                $response = (new CreateAction())->execute($data);
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }
            DB::commit();
            $this->dispatch('multiple-terms-saved');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Successfully created '.count($terms).' payment terms.');
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

    // ─── Pay Selected ───────────────────────────────────────────

    #[On('paySelectedTermsFromJS')]
    public function paySelectedTermsFromJS($ids)
    {
        $this->openPaySelectedModal($ids);
    }

    public function openPaySelectedModal($ids)
    {
        $rentOut = $this->getRentOut();
        $terms = RentOutPaymentTerm::whereIn('id', $ids)->where('balance', '>', 0)->get();
        $cashTerms = $terms->map(function ($term) use ($rentOut) {
            return [
                'id' => $term->id,
                'date' => $term->due_date?->format('d-m-Y'),
                'customer' => $rentOut->customer?->name ?? '',
                'property' => $rentOut->property?->number ?? '',
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
            $this->dispatch('payment-submitted');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Payment submitted successfully.');
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
