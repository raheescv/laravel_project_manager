<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Commission\CreateAction;
use App\Actions\RentOut\Commission\UpdateAction;
use App\Models\RentOut;
use App\Models\RentOutCommission;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class CommissionModal extends Component
{
    public ?int $rentOutId = null;

    public ?int $commissionId = null;

    public string $salesmanName = '';

    /** @var array<string, mixed> */
    public array $form = [];

    #[On('open-commission-modal')]
    public function openModal(int $rentOutId, ?int $commissionId = null): void
    {
        $rentOut = RentOut::with('salesman:id,name')->findOrFail($rentOutId);
        if (! $rentOut->salesman_id) {
            $this->dispatch('error', message: 'Set a salesman on this agreement before adding a commission.');

            return;
        }

        $this->rentOutId = $rentOut->id;
        $this->salesmanName = (string) $rentOut->salesman?->name;
        $this->commissionId = null;

        $this->form = [
            'date' => now()->format('Y-m-d'),
            'commission_type' => RentOutCommission::TYPE_PERCENTAGE,
            'commission_percentage' => '',
            'contract_amount' => (float) $rentOut->rent,
            'amount' => '',
            'remark' => '',
        ];

        if ($commissionId) {
            $commission = RentOutCommission::where('rent_out_id', $rentOut->id)->findOrFail($commissionId);
            $this->commissionId = $commission->id;
            $this->form = [
                'date' => $commission->date->format('Y-m-d'),
                'commission_type' => $commission->commission_type,
                'commission_percentage' => (float) $commission->commission_percentage,
                'contract_amount' => (float) $commission->contract_amount,
                'amount' => (float) $commission->amount,
                'remark' => $commission->remark,
            ];
        }

        $this->resetValidation();
        $this->dispatch('ToggleCommissionModal');
    }

    public function getCalculatedAmountProperty(): float
    {
        return RentOutCommission::calculate(
            (string) ($this->form['commission_type'] ?? RentOutCommission::TYPE_PERCENTAGE),
            is_numeric($this->form['contract_amount'] ?? null) ? (float) $this->form['contract_amount'] : 0,
            is_numeric($this->form['commission_percentage'] ?? null) ? (float) $this->form['commission_percentage'] : 0,
            is_numeric($this->form['amount'] ?? null) ? (float) $this->form['amount'] : 0,
        );
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can($this->commissionId ? 'rent out commission.edit' : 'rent out commission.create'), 403);

        $isPercentage = ($this->form['commission_type'] ?? '') === RentOutCommission::TYPE_PERCENTAGE;
        $this->validate([
            'form.date' => 'required|date',
            'form.commission_type' => 'required|in:percentage,fixed',
            'form.contract_amount' => 'required|numeric|min:0',
            'form.commission_percentage' => $isPercentage ? 'required|numeric|gt:0|max:100' : 'nullable',
            'form.amount' => $isPercentage ? 'nullable' : 'required|numeric|gt:0',
        ], [
            'form.commission_percentage.required' => 'Commission % is required.',
            'form.commission_percentage.gt' => 'Commission % must be greater than zero.',
            'form.amount.required' => 'Commission amount is required.',
            'form.amount.gt' => 'Commission amount must be greater than zero.',
        ]);

        try {
            DB::beginTransaction();
            $data = array_merge($this->form, ['rent_out_id' => $this->rentOutId]);
            $response = $this->commissionId
                ? (new UpdateAction())->execute($data, $this->commissionId, Auth::id())
                : (new CreateAction())->execute($data, Auth::id());
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('ToggleCommissionModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', message: $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.commission-modal');
    }
}
