<?php

namespace App\Livewire\RentOut\Tabs;

use App\Enums\RentOut\AgreementType;
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
                'start_date' => $rentOut->end_date->format('Y-m-d'),
                'end_date' => $rentOut->end_date->addYear()->format('Y-m-d'),
                'rent_amount' => $rentOut->rent ?? 0,
                'payment_mode' => $rentOut->collection_payment_mode->value,
                'remarks' => '',
            ],
            editingId: null,
        );
    }

    public function editExtend($id)
    {
        abort_unless(auth()->user()?->can($this->permissionPrefix().'.extend edit'), 403);
        $extend = RentOutExtend::where('rent_out_id', $this->rentOutId)->find($id);
        if (! $extend) {
            return;
        }

        $this->dispatch('open-extend-modal',
            form: [
                'rent_out_id' => $extend->rent_out_id,
                'start_date' => $extend->start_date?->format('Y-m-d'),
                'end_date' => $extend->end_date?->format('Y-m-d'),
                'rent_amount' => $extend->rent_amount,
                'payment_mode' => $extend->payment_mode?->value,
                'remarks' => $extend->remarks ?? '',
            ],
            editingId: $extend->id,
        );
    }

    public function deleteExtend($id)
    {
        abort_unless(auth()->user()?->can($this->permissionPrefix().'.extend delete'), 403);
        try {
            DB::beginTransaction();
            $extend = RentOutExtend::where('rent_out_id', $this->rentOutId)->find($id);
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

    protected function permissionPrefix(): string
    {
        return RentOut::whereKey($this->rentOutId)->value('agreement_type') === AgreementType::Lease ? 'rent out lease' : 'rent out';
    }

    public function render()
    {
        $rentOut = RentOut::with('extends')->find($this->rentOutId);

        return view('livewire.rent-out.tabs.extend-tab', ['rentOut' => $rentOut, 'permissionPrefix' => $this->permissionPrefix()]);
    }
}
