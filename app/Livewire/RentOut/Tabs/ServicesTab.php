<?php

namespace App\Livewire\RentOut\Tabs;

use App\Actions\RentOut\Service\DeleteAction;
use App\Models\RentOut;
use App\Models\RentOutService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ServicesTab extends Component
{
    public $rentOutId;

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    #[On('rent-out-updated')]
    public function refresh() {}

    public function openServiceModal()
    {
        $rentOut = RentOut::find($this->rentOutId);

        $this->dispatch('open-service-modal',
            form: [
                'rent_out_id' => $rentOut->id,
                'name' => '',
                'amount' => 0,
                'description' => '',
            ],
            editingId: null,
        );
    }

    public function editService($id)
    {
        $service = RentOutService::find($id);
        if (! $service) {
            return;
        }

        $this->dispatch('open-service-modal',
            form: [
                'rent_out_id' => $service->rent_out_id,
                'name' => $service->name ?? '',
                'amount' => $service->amount,
                'description' => $service->description ?? '',
            ],
            editingId: $id,
        );
    }

    public function deleteService($id)
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
        $rentOut = RentOut::with('services')->find($this->rentOutId);

        return view('livewire.rent-out.tabs.services-tab', [
            'rentOut' => $rentOut,
        ]);
    }
}
