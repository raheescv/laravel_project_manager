<?php

namespace App\Livewire\PurchaseRequest;

use App\Enums\PurchaseRequest\PurchaseRequestStatus;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class View extends Component
{
    public PurchaseRequest $purchase_request;

    public bool $is_approvable = false;

    public string $remarks = '';

    public function mount(int $purchase_request_id, bool $is_approvable = false)
    {
        $this->purchase_request = PurchaseRequest::with(['products.product.brand', 'products.product.mainCategory', 'products.product.subCategory', 'products.product.unit', 'creator', 'branch', 'decisionMaker'])->findOrFail($purchase_request_id);

        $this->is_approvable = $is_approvable;
    }

    public function approve()
    {
        $data = [
            'status' => PurchaseRequestStatus::APPROVED,
            'decision_by' => Auth::id(),
            'decision_at' => now(),
            'decision_note' => $this->remarks,
        ];
        $this->purchase_request->update($data);

        $this->dispatch('success', ['message' => 'Approved successfully']);

        $this->navigateBack();
    }

    public function reject()
    {
        $this->validate(['remarks' => 'required|string|min:3']);

        $this->purchase_request->update([
            'status' => PurchaseRequestStatus::REJECTED,
            'decision_by' => Auth::id(),
            'decision_at' => now(),
            'decision_note' => $this->remarks,
        ]);

        $this->dispatch('success', ['message' => 'Rejected successfully']);

        $this->navigateBack();
    }

    public function completed()
    {
        $this->purchase_request->update(['status' => PurchaseRequestStatus::COMPLETED]);

        $this->dispatch('success', ['message' => 'Completed successfully']);

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
