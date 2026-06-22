<?php

namespace App\Livewire\LocalPurchaseOrder;

use App\Enums\LocalPurchaseOrder\LocalPurchaseOrderStatus;
use App\Models\LocalPurchaseOrder;
use Livewire\Component;

class View extends Component
{
    public LocalPurchaseOrder $order;

    public bool $is_approvable = false;

    public bool $is_confirmable = false;

    public string $remarks = '';

    public string $confirm_remarks = '';

    public function mount(int $local_purchase_order_id, bool $is_approvable = false, bool $is_confirmable = false)
    {
        $this->order = LocalPurchaseOrder::with(['vendor', 'creator', 'branch', 'decisionMaker', 'confirmedBy', 'items.product.brand', 'items.product.mainCategory', 'items.product.subCategory', 'items.product.unit', 'items.account', 'grns.items.product'])
            ->findOrFail($local_purchase_order_id);

        $this->is_approvable = $is_approvable;
        $this->is_confirmable = $is_confirmable;
    }

    public function approve()
    {
        abort_unless(auth()->user()?->can('local purchase order.decide'), 403);
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
        abort_unless(auth()->user()?->can('local purchase order.decide'), 403);
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

    public function confirm()
    {
        abort_unless(auth()->user()?->can('local purchase order.confirm'), 403);
        abort_unless($this->order->status === LocalPurchaseOrderStatus::APPROVED, 403);

        $this->order->update([
            'status' => LocalPurchaseOrderStatus::CONFIRMED,
            'confirmation_by' => auth()->id(),
            'confirmation_at' => now(),
            'confirmation_note' => $this->confirm_remarks,
        ]);

        $this->dispatch('success', ['message' => 'Confirmed successfully']);

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
