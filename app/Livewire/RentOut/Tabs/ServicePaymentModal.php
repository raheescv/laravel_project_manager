<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Payment\StoreTransactionAction;
use App\Models\Account;
use App\Models\RentOutTransaction;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ServicePaymentModal extends Component
{
    public ?int $rentOutId = null;

    public array $form = [
        'date' => '',
        'amount' => 0,
        'category' => '',
        'account_id' => '',
        'remark' => '',
    ];

    #[On('open-service-payment-modal')]
    public function openModal($rentOutId)
    {
        $this->rentOutId = $rentOutId;
        $this->form = [
            'date' => now()->format('Y-m-d'),
            'amount' => 0,
            'category' => '',
            'account_id' => '',
            'remark' => '',
        ];
        $this->resetValidation();
        $this->dispatch('ToggleServicePaymentModal');
    }

    public function payLater()
    {
        $this->validate([
            'form.date' => 'required|date',
            'form.amount' => 'required|numeric|min:0.01',
            'form.category' => 'required|string',
        ], [
            'form.date.required' => 'Date is required.',
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
            'form.category.required' => 'Category is required.',
        ]);

        try {
            DB::beginTransaction();

            $response = (new StoreTransactionAction())->charge($this->rentOutId, $this->servicePaymentData());

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('ToggleServicePaymentModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Service charge recorded (Pay Later).');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function payNow()
    {
        $this->validate([
            'form.date' => 'required|date',
            'form.amount' => 'required|numeric|min:0.01',
            'form.category' => 'required|string',
            'form.account_id' => 'required',
        ], [
            'form.date.required' => 'Date is required.',
            'form.amount.required' => 'Amount is required.',
            'form.amount.min' => 'Amount must be greater than zero.',
            'form.category.required' => 'Category is required.',
            'form.account_id.required' => 'Payment method is required.',
        ]);

        try {
            DB::beginTransaction();

            $response = (new StoreTransactionAction())->chargeAndPay($this->rentOutId, $this->servicePaymentData());

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            DB::commit();
            $this->dispatch('ToggleServicePaymentModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Service payment recorded (Pay Now).');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    protected function servicePaymentData(): array
    {
        return [
            'date' => $this->form['date'],
            'amount' => $this->form['amount'],
            'account_id' => $this->form['account_id'] ?? '',
            'source' => 'Service',
            'model' => 'RentOutService',
            'paid_date' => $this->form['date'],
            'reason' => $this->form['category'] ?: 'Service Payment',
            'group' => 'Service',
            'category' => $this->form['category'],
            'payment_type' => 'Services',
            'remark' => $this->form['remark'] ?? '',
            'created_by' => auth()->id(),
        ];
    }

    public function render()
    {
        $serviceCharges = collect();
        if ($this->rentOutId) {
            $serviceCharges = RentOutTransaction::where('rent_out_id', $this->rentOutId)
                ->whereIn('source', ['Service', 'ServiceCharge'])
                ->selectRaw('category, sum(credit) as credit, sum(debit) as debit')
                ->groupBy('category')
                ->get();
        }

        // Resolve category IDs to names
        $categoryIds = $serviceCharges->pluck('category')->filter()->unique()->values()->toArray();
        $categoryNames = Account::whereIn('id', $categoryIds)->pluck('name', 'id')->toArray();

        // Add category_name to each row
        $serviceCharges->each(function ($row) use ($categoryNames) {
            $row->category_name = $categoryNames[$row->category] ?? $row->category;
        });

        return view('livewire.rent-out.tabs.service-payment-modal', [
            'paymentMethods' => paymentMethodsOptions(),
            'serviceCharges' => $serviceCharges,
        ]);
    }
}
