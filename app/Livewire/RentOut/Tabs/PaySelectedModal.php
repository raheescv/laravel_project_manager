<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\RentOut;
use App\Models\RentOutPaymentTerm;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class PaySelectedModal extends Component
{
    public $rentOutId;
    public $showModal = false;
    public $saving = false;

    // Header fields
    public $payDate;
    public $payPaymentMode = 'cash';
    public $payRemark = '';

    // Row data
    public $cashTerms = [];

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $this->payDate = now()->format('Y-m-d');
    }

    #[On('paySelectedTermsFromJS')]
    public function openFromJS($ids)
    {
        $this->openPaySelectedModal($ids);
    }

    public function openPaySelectedModal($ids)
    {
        $rentOut = RentOut::with(['customer', 'property'])->find($this->rentOutId);
        $terms = RentOutPaymentTerm::whereIn('id', $ids)->where('balance', '>', 0)->get();

        $this->cashTerms = $terms->map(function ($term) use ($rentOut) {
            return [
                'id' => $term->id,
                'date' => $term->due_date?->format('d-m-Y'),
                'customer' => $rentOut->customer?->name ?? '',
                'property' => $rentOut->property?->number ?? '',
                'balance' => (float) $term->balance,
                'amount' => (float) $term->balance,
                'payment_mode' => 'cash',
                'remark' => '',
            ];
        })->toArray();

        $this->payDate = now()->format('Y-m-d');
        $this->payPaymentMode = 'cash';
        $this->payRemark = '';
        $this->saving = false;
        $this->showModal = true;
    }

    public function applyModeToAll()
    {
        $this->applyToAllRows('payment_mode', $this->payPaymentMode);
    }

    public function applyRemarkToAll()
    {
        $this->applyToAllRows('remark', $this->payRemark);
    }

    protected function applyToAllRows(string $field, mixed $value): void
    {
        foreach ($this->cashTerms as &$ct) {
            $ct[$field] = $value;
        }
    }

    public function submit()
    {
        $this->saving = true;

        try {
            DB::beginTransaction();
            foreach ($this->cashTerms as $cashTerm) {
                $term = RentOutPaymentTerm::find($cashTerm['id']);
                if ($term && $cashTerm['amount'] > 0) {
                    $term->paid = ($term->paid ?? 0) + $cashTerm['amount'];
                    $term->payment_mode = $cashTerm['payment_mode'] ?? $this->payPaymentMode;
                    $term->paid_date = $this->payDate;
                    $term->save();
                }
            }
            DB::commit();
            $this->showModal = false;
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Payment submitted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }

        $this->saving = false;
    }

    public function closeModal()
    {
        $this->showModal = false;
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
