<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Security\DeleteAction;
use App\Models\RentOut;
use App\Models\RentOutSecurity;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class SecurityTab extends Component
{
    public $rentOutId;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    #[On('rent-out-updated')]
    public function refresh() {}

    public function openSecurityModal()
    {
        $rentOut = RentOut::find($this->rentOutId);

        $this->dispatch('open-security-modal',
            form: [
                'rent_out_id' => $rentOut->id,
                'amount' => 0,
                'payment_mode' => 'cash',
                'bank_name' => '',
                'cheque_no' => '',
                'type' => 'deposit',
                'status' => 'pending',
                'due_date' => now()->format('Y-m-d'),
                'remarks' => '',
            ],
            editingId: null,
        );
    }

    public function editSecurity($id)
    {
        $security = RentOutSecurity::find($id);
        if (! $security) {
            return;
        }

        $this->dispatch('open-security-modal',
            form: [
                'rent_out_id' => $security->rent_out_id,
                'amount' => $security->amount,
                'payment_mode' => $security->payment_mode?->value ?? 'cash',
                'bank_name' => $security->bank_name ?? '',
                'cheque_no' => $security->cheque_no ?? '',
                'type' => $security->type?->value ?? 'deposit',
                'status' => $security->status?->value ?? 'pending',
                'due_date' => $security->due_date?->format('Y-m-d') ?? '',
                'remarks' => $security->remarks ?? '',
            ],
            editingId: $id,
        );
    }

    public function deleteSecurity($id)
    {
        try {
            DB::beginTransaction();
            $response = (new DeleteAction)->execute($id);
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

    public function render()
    {
        $rentOut = RentOut::with('securities')->find($this->rentOutId);

        return view('livewire.rent-out.tabs.security-tab', [
            'rentOut' => $rentOut,
        ]);
    }
}
