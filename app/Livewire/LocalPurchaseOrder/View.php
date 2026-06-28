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

    public bool $editing_terms = false;

    public array $terms_buffer = [];

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

    public function openTermsEdit(): void
    {
        $existing = $this->order->payment_terms ?? [];
        $this->terms_buffer = count($existing)
            ? $existing
            : [['label' => 'Payment Terms', 'value' => '']];
        $this->editing_terms = true;
    }

    public function addTermRow(): void
    {
        $this->terms_buffer[] = ['label' => '', 'value' => ''];
    }

    public function addQuickTerm(string $label): void
    {
        $exists = collect($this->terms_buffer)->contains(fn ($t) => strtolower($t['label']) === strtolower($label));
        if (! $exists) {
            $this->terms_buffer[] = ['label' => $label, 'value' => ''];
        }
    }

    public function removeTermRow(int $index): void
    {
        array_splice($this->terms_buffer, $index, 1);
        $this->terms_buffer = array_values($this->terms_buffer);
    }

    public function saveTerms(): void
    {
        abort_unless(auth()->user()?->can('editTerms', $this->order), 403);

        $terms = collect($this->terms_buffer)
            ->filter(fn ($t) => filled($t['label'] ?? ''))
            ->map(fn ($t) => ['label' => trim($t['label']), 'value' => trim($t['value'] ?? '')])
            ->values()
            ->all();

        $this->order->update(['payment_terms' => count($terms) ? $terms : null]);
        $this->order->refresh();
        $this->editing_terms = false;
        $this->dispatch('success', ['message' => 'Terms saved']);
    }

    public function cancelTermsEdit(): void
    {
        $this->editing_terms = false;
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
