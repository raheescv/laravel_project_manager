<?php

namespace App\Livewire\Grn;

use App\Enums\Grn\GrnStatus;
use App\Models\Grn;
use Livewire\Component;

class View extends Component
{
    public Grn $grn;

    public bool $is_approvable = false;

    public string $remarks = '';

    public function mount(int $grn_id, bool $is_approvable = false)
    {
        $this->grn = Grn::with(['localPurchaseOrder.vendor', 'creator', 'branch', 'decisionMaker', 'items.product.brand', 'items.product.mainCategory', 'items.product.subCategory', 'items.product.unit'])
            ->findOrFail($grn_id);

        $this->is_approvable = $is_approvable;
    }

    public function accept()
    {
        $this->grn->update([
            'status' => GrnStatus::ACCEPTED,
            'decision_by' => auth()->id(),
            'decision_at' => now(),
            'decision_note' => $this->remarks,
        ]);

        $this->dispatch('success', ['message' => 'GRN accepted successfully']);

        $this->navigateBack();
    }

    public function reject()
    {
        $this->validate([
            'remarks' => 'required|string|min:3',
        ]);

        $this->grn->update([
            'status' => GrnStatus::REJECTED,
            'decision_by' => auth()->id(),
            'decision_at' => now(),
            'decision_note' => $this->remarks,
        ]);

        $this->dispatch('success', ['message' => 'GRN rejected successfully']);

        $this->navigateBack();
    }

    private function navigateBack()
    {
        return $this->redirect(route('grn::view', $this->grn->id), true);
    }

    public function render()
    {
        return view('livewire.grn.view');
    }
}
