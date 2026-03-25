<?php

namespace App\Livewire\PurchaseRequest;

use App\Enums\PurchaseRequest\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use Livewire\Component;

class View extends Component
{
    public PurchaseRequest $purchase_request;
    public bool $is_approvable = false;
    public string $remarks = '';

    public function mount(int $purchase_request_id, bool $is_approvable = false)
    {
        $this->purchase_request = PurchaseRequest::with('products.product', 'creator')
            ->findOrFail($purchase_request_id);

        $this->is_approvable = $is_approvable;
    }

    public function approve()
    {
        $this->purchase_request->update([
            'status' => PurchaseRequestStatus::APPROVED,
            'decision_by' => auth()->id(),
            'decision_at' => now(),
            'decision_note' => $this->remarks,
        ]);

        $this->dispatch('success', ['message' => 'Approved successfully']);

        $this->navigateBack();
    }

    public function reject()
    {
        $this->validate([
            'remarks' => 'required|string|min:3',
        ]);

        $this->purchase_request->update([
            'status' => PurchaseRequestStatus::REJECTED,
            'decision_by' => auth()->id(),
            'decision_at' => now(),
            'decision_note' => $this->remarks,
        ]);

        $this->dispatch('success', ['message' => 'Rejected successfully']);

        $this->navigateBack();
    }

    private function navigateBack()
    {
        return $this->redirect(route('purchase-request::view', $this->purchase_request->id), true);
    }

    public function render()
    {
        return view('livewire.purchase-request.view');
    }
}
