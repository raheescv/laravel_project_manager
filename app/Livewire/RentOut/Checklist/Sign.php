<?php

namespace App\Livewire\RentOut\Checklist;

use App\Actions\RentOut\Checklist\SaveSignatureAction;
use App\Models\RentOut;
use Livewire\Component;

class Sign extends Component
{
    public $signature;

    public RentOut $rentOut;

    public string $phase;

    public string $role;

    public ?string $signerName = null;

    public ?int $userId = null;

    public function mount(RentOut $rentOut, string $phase, string $role, ?string $signerName = null, ?int $userId = null)
    {
        $this->rentOut = $rentOut;
        $this->phase = $phase;
        $this->role = $role;
        $this->signerName = $signerName;
        $this->userId = $userId;
    }

    public function save()
    {
        $this->validate([
            'signature' => 'required|string',
        ]);

        (new SaveSignatureAction())->execute([
            'rent_out_id' => $this->rentOut->id,
            'phase' => $this->phase,
            'role' => $this->role,
            'user_id' => $this->userId,
            'signer_name' => $this->signerName,
            'signature' => $this->signature,
        ]);

        return redirect(route('property::rent_out::checklist::print', $this->rentOut->id));
    }

    public function render()
    {
        return view('livewire.rent-out.checklist.sign');
    }
}
