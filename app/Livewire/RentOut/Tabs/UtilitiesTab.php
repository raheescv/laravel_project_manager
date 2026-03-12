<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\RentOut;
use Livewire\Attributes\On;
use Livewire\Component;

class UtilitiesTab extends Component
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
        $rentOut = RentOut::with('utilityTerms.utility')->find($this->rentOutId);

        return view('livewire.rent-out.tabs.utilities-tab', ['rentOut' => $rentOut]);
    }
}
