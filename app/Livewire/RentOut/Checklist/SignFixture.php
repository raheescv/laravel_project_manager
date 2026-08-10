<?php

namespace App\Livewire\RentOut\Checklist;

use App\Actions\RentOut\Checklist\SaveFixtureSignatureAction;
use App\Models\RentOut;
use App\Models\RentOutFixtureArea;
use Livewire\Component;

/**
 * Owner acceptance pad for one Fixture Comments area — the counterpart to Sign.php,
 * which handles the handover signatories at the foot of the form.
 */
class SignFixture extends Component
{
    public $signature;

    public RentOut $rentOut;

    public int $areaId;

    public string $category;

    public ?string $ownerName = null;

    public function mount(RentOut $rentOut, RentOutFixtureArea $area, ?string $ownerName = null)
    {
        $this->rentOut = $rentOut;
        $this->areaId = $area->id;
        $this->category = $area->category;
        $this->ownerName = $ownerName ?: $area->owner_name;
    }

    public function save()
    {
        abort_unless(auth()->user()?->can('rent out checklist.edit'), 403);
        $this->validate([
            'signature' => 'required|string',
        ]);

        (new SaveFixtureSignatureAction())->execute([
            'rent_out_id' => $this->rentOut->id,
            'area_id' => $this->areaId,
            'owner_name' => $this->ownerName,
            'signature' => $this->signature,
        ]);

        return redirect(route('property::rent_out::checklist::print', $this->rentOut->id));
    }

    public function render()
    {
        return view('livewire.rent-out.checklist.sign-fixture');
    }
}
