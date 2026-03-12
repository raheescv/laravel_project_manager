<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\RentOut;
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

    public function render()
    {
        $rentOut = RentOut::with('services')->find($this->rentOutId);

        return view('livewire.rent-out.tabs.services-tab', ['rentOut' => $rentOut]);
    }
}
