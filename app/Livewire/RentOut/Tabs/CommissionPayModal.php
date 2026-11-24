<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Commission\PayAction;
use App\Models\RentOutCommission;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class CommissionPayModal extends Component
{
    public ?int $commissionId = null;

    public float $amount = 0;

    public string $agentName = '';

    /** @var array{paid_date: string, payment_account_id: int|string} */
    public array $form = ['paid_date' => '', 'payment_account_id' => ''];

    #[On('open-commission-pay-modal')]
    public function openModal(int $commissionId): void
    {
        $commission = RentOutCommission::with('agent:id,name')->findOrFail($commissionId);
        $this->commissionId = $commission->id;
        $this->amount = (float) $commission->amount;
        $this->agentName = (string) $commission->agent?->name;
        $this->form = ['paid_date' => now()->format('Y-m-d'), 'payment_account_id' => ''];
        $this->resetValidation();
        $this->dispatch('ToggleCommissionPayModal');
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('rent out commission.pay'), 403);
        $this->validate([
            'form.paid_date' => 'required|date',
            'form.payment_account_id' => 'required',
        ], [
            'form.paid_date.required' => 'Date is required.',
            'form.payment_account_id.required' => 'Payment method is required.',
        ]);

        try {
            DB::beginTransaction();
            $response = (new PayAction())->execute((int) $this->commissionId, $this->form, Auth::id());
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('ToggleCommissionPayModal');
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', message: $th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rent-out.tabs.commission-pay-modal', [
            'paymentMethods' => paymentMethodsOptions(),
        ]);
    }
}
