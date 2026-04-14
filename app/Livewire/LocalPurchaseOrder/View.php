<?php

namespace App\Livewire\LocalPurchaseOrder;

use App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus;
use App\Models\LocalPurchaseOrder;
use Livewire\Component;

class View extends Component
{
    public LocalPurchaseOrder $order;

    public bool $is_approvable = false;

    public string $remarks = '';

    public function mount(int $local_purchase_order_id, bool $is_approvable = false)
    {
        $this->order = LocalPurchaseOrder::with(['vendor', 'creator', 'branch', 'decisionMaker', 'items.product.brand', 'items.product.mainCategory', 'items.product.subCategory', 'items.product.unit', 'grns.items.product'])
            ->findOrFail($local_purchase_order_id);

        $this->is_approvable = $is_approvable;
    }

    public function approve()
    {
        $this->order->update([
            'status' => LocalPurchaseOrderStatus::APPROVED,
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

        $this->order->update([
            'status' => LocalPurchaseOrderStatus::REJECTED,
            'decision_by' => auth()->id(),
            'decision_at' => now(),
            'decision_note' => $this->remarks,
        ]);

        $this->dispatch('success', ['message' => 'Rejected successfully']);

        $this->navigateBack();
    }

    private function navigateBack()
    {
        return $this->redirect(route('lpo::view', $this->order->id), true);
    }

    public function render()
    {
        return view('livewire.local-purchase-order.view');
    }
}
