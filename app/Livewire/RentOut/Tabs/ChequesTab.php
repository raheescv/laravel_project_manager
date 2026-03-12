<?php

namespace App\Livewire\RentOut\Tabs;

use App\Models\RentOut;
use Livewire\Attributes\On;
use Livewire\Component;

class ChequesTab extends Component
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
        $rentOut = RentOut::with('cheques')->find($this->rentOutId);

        return view('livewire.rent-out.tabs.cheques-tab', ['rentOut' => $rentOut]);
    }
}
