<?php

namespace App\Livewire\RentOut\Tabs;

use App\Helpers\Facades\RentOutTransactionHelper;
use App\Models\RentOut;
use App\Models\RentOutUtilityTerm;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class UtilityPaySelectedModal extends Component
{
    public $rentOutId;

    public $saving = false;

    // Header fields
    public $payDate;

    public $payPaymentMode = 1;

    public $payRemark = '';

    // Row data
    public $cashTerms = [];

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $this->payDate = now()->format('Y-m-d');
    }

    #[On('open-utility-pay-selected-modal')]
    public function openModal($ids)
    {
        $rentOut = RentOut::with(['customer', 'property'])->find($this->rentOutId);
        $terms = RentOutUtilityTerm::with('utility')
            ->whereIn('id', $ids)
            ->where('balance', '>', 0)
            ->get();

        $this->cashTerms = $terms->map(function ($term) use ($rentOut) {
            return [
                'id' => $term->id,
                'date' => $term->date?->format('d-m-Y'),
                'customer' => $rentOut->customer?->name ?? '',
                'property' => $rentOut->property?->number ?? '',
                'utility' => $term->utility?->name ?? '',
                'balance' => (float) $term->balance,
                'amount' => (float) $term->balance,
                'payment_mode' => 1,
                'remark' => '',
            ];
        })->toArray();

        $this->payDate = now()->format('Y-m-d');
        $this->payPaymentMode = 1;
        $this->payRemark = '';
        $this->saving = false;
        $this->dispatch('ToggleUtilityPaySelectedModal');
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
            $rentOut = RentOut::find($this->rentOutId);
            foreach ($this->cashTerms as $cashTerm) {
                $term = RentOutUtilityTerm::with('utility')->find($cashTerm['id']);
                if ($term && $cashTerm['amount'] > 0) {
                    $paymentMode = $cashTerm['payment_mode'] ?? $this->payPaymentMode;
                    $term->paid = ($term->paid ?? 0) + $cashTerm['amount'];
                    $term->payment_mode = $paymentMode;
                    $term->paid_date = $this->payDate;
                    $term->save();

                    $response = RentOutTransactionHelper::storeUtilityPayment(
                        $this->rentOutId,
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
            }
            DB::commit();
            $this->dispatch('ToggleUtilityPaySelectedModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Utility payment submitted successfully.');
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
        return view('livewire.rent-out.tabs.utility-pay-selected-modal', [
            'paymentMethods' => paymentMethodsOptions(),
        ]);
    }
}
