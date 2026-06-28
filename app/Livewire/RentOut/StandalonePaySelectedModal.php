<?php

namespace App\Livewire\RentOut;

use App\Enums\RentOut\AgreementType;
use App\Helpers\Facades\RentOutTransactionHelper;
use App\Models\RentOutPaymentTerm;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class StandalonePaySelectedModal extends Component
{
    public bool $saving = false;

    public string $payDate = '';

    public int|string $payPaymentMode = 1;

    public string $payRemark = '';

    public array $cashTerms = [];

    public function mount()
    {
        $this->payDate = now()->format('Y-m-d');
    }

    #[On('paySelectedTermsFromJS')]
    public function openFromJS($ids)
    {
        $terms = RentOutPaymentTerm::with(['rentOut.customer', 'rentOut.property'])
            ->whereIn('id', $ids)
            ->where('balance', '>', 0)
            ->get();

        $this->cashTerms = $terms->map(function ($term) {
            return [
                'id' => $term->id,
                'rent_out_id' => $term->rent_out_id,
                'date' => $term->due_date?->format('d-m-Y'),
                'customer' => $term->rentOut?->customer?->name ?? '',
                'property' => $term->rentOut?->property?->number ?? '',
                'balance' => (float) $term->balance,
                'amount' => (float) $term->balance,
                'payment_mode' => $this->payPaymentMode,
                'remark' => '',
            ];
        })->toArray();

        $this->payDate = now()->format('Y-m-d');
        $this->payPaymentMode = 1;
        $this->payRemark = '';
        $this->saving = false;
        $this->dispatch('TogglePaySelectedModal');
    }

    public function applyModeToAll()
    {
        foreach ($this->cashTerms as &$ct) {
            $ct['payment_mode'] = $this->payPaymentMode;
        }
    }

    public function applyRemarkToAll()
    {
        foreach ($this->cashTerms as &$ct) {
            $ct['remark'] = $this->payRemark;
        }
    }

    public function submit()
    {
        $this->saving = true;

        try {
            DB::beginTransaction();
            foreach ($this->cashTerms as $cashTerm) {
                $term = RentOutPaymentTerm::find($cashTerm['id']);
                if (! $term || $cashTerm['amount'] <= 0) {
                    continue;
                }

                $rentOut = $term->rentOut;
                abort_unless(
                    auth()->user()?->can($rentOut?->agreement_type === AgreementType::Lease ? 'rent out lease.payment' : 'rent out.payment'),
                    403
                );

                $paymentMode = $cashTerm['payment_mode'] ?? $this->payPaymentMode;
                $term->paid = ($term->paid ?? 0) + $cashTerm['amount'];
                $term->payment_mode = $paymentMode;
                $term->paid_date = $this->payDate;
                $term->save();

                $response = RentOutTransactionHelper::storeRentPayment(
                    $term->rent_out_id,
                    $term,
                    $cashTerm['amount'],
                    $paymentMode,
                    $this->payDate,
                    $cashTerm['remark'] ?? ''
                );

                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }
            DB::commit();
            $this->dispatch('TogglePaySelectedModal');
            $this->dispatch('success', message: 'Payment submitted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }

        $this->saving = false;
    }

    public function getPayingTotalProperty()
    {
        return array_sum(array_column($this->cashTerms, 'amount'));
    }

    public function getBalanceTotalProperty()
    {
        return array_sum(array_column($this->cashTerms, 'balance'));
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.pay-selected-modal', [
            'paymentMethods' => paymentMethodsOptions(),
        ]);
    }
}
