<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\RentOut;
use App\Models\RentOutExtend;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ExtendTab extends Component
{
    public $rentOutId;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    #[On('rent-out-updated')]
    public function refresh() {}

    public function openExtendModal()
    {
        $rentOut = RentOut::find($this->rentOutId);

        $this->dispatch('open-extend-modal',
            form: [
                'rent_out_id' => $rentOut->id,
                'start_date' => $rentOut->end_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'end_date' => $rentOut->end_date?->addYear()->format('Y-m-d') ?? now()->addYear()->format('Y-m-d'),
                'rent_amount' => $rentOut->rent ?? 0,
                'payment_mode' => $rentOut->collection_payment_mode?->value ?? 'cash',
                'remarks' => '',
            ],
            editingId: null,
        );
    }

    public function deleteExtend($id)
    {
        try {
            DB::beginTransaction();
            $extend = RentOutExtend::find($id);
            if (! $extend) {
                throw new \Exception('Extension not found.');
            }
            $extend->delete();
            DB::commit();
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: 'Successfully deleted extension.');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function render()
    {
        $rentOut = RentOut::with('extends')->find($this->rentOutId);

        return view('livewire.rent-out.tabs.extend-tab', ['rentOut' => $rentOut]);
    }
}
