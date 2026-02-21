<?php

namespace App\Livewire\Tailoring;

use App\Models\TailoringOrder;
use App\Models\TailoringOrderItem;
use App\Models\TailoringOrderItemTailor;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OrderTailorActionModal extends Component
{
    public $selectedTailorOrderDetails = [];

    public $selectedTailorAssignments = [];

    public $tailorStatusOptions = [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'delivered' => 'Delivered',
    ];

    protected $listeners = [
        'open-tailor-action-modal' => 'openTailorActionModal',
    ];

    public function openTailorActionModal($orderId = null): void
    {
        if (!$orderId) {
            $this->dispatch('error', ['message' => 'Invalid order']);

            return;
        }

        $order = $this->getOrderForTailorActionModal($orderId);

        if (! $order) {
            $this->dispatch('error', ['message' => 'Order not found']);

            return;
        }

        $this->setTailorActionModalData($order);
        $this->dispatch('toggle-order-tailor-actions-modal');
    }

    public function updateTailorAssignmentStatus($assignmentId, $status): void
    {
        $status = (string) $status;
        if (! in_array($status, array_keys($this->tailorStatusOptions), true)) {
            $this->dispatch('error', ['message' => 'Invalid tailor status selected']);

            return;
        }

        $assignment = TailoringOrderItemTailor::with('tailoringOrderItem.order')->find($assignmentId);

        if (! $assignment || ! $assignment->tailoringOrderItem || ! $assignment->tailoringOrderItem->order) {
            $this->dispatch('error', ['message' => 'Tailor assignment not found']);

            return;
        }

        $selectedOrderId = (int) ($this->selectedTailorOrderDetails['id'] ?? 0);
        $assignmentOrderId = (int) $assignment->tailoringOrderItem->order->id;
        if ($selectedOrderId > 0 && $selectedOrderId !== $assignmentOrderId) {
            $this->dispatch('error', ['message' => 'This assignment does not belong to the selected order']);

            return;
        }

        $assignment->status = $status;
        $assignment->updated_by = Auth::id();
        $assignment->save();

        $this->recalculateItemTailorSummary($assignment->tailoringOrderItem);

        $order = $this->getOrderForTailorActionModal($assignmentOrderId);
        if ($order) {
            $this->setTailorActionModalData($order);
        }

        $this->dispatch('success', ['message' => 'Tailor status updated successfully']);
    }

    protected function recalculateItemTailorSummary(TailoringOrderItem $item): void
    {
        $savedAssignments = $item->tailorAssignments()->orderBy('id')->get();
        $completedUnits = $savedAssignments->filter(fn ($assignment) => in_array($assignment->status, ['completed', 'delivered']))->count();
        $deliveredUnits = $savedAssignments->filter(fn ($assignment) => $assignment->status === 'delivered')->count();
        $totalCommission = $savedAssignments->sum('tailor_commission');

        $item->forceFill([
            'item_completion_date' => $savedAssignments->last()?->completion_date,
            'completed_quantity' => (float) $completedUnits,
            'delivered_quantity' => (float) $deliveredUnits,
            'tailor_total_commission' => $totalCommission,
        ])->save();
    }

    protected function getOrderForTailorActionModal(int $orderId): ?TailoringOrder
    {
        return TailoringOrder::with([
            'account:id,name,mobile',
            'items:id,tailoring_order_id,item_no,product_name,quantity,completion_status,delivery_status',
            'items.tailorAssignments:id,tailoring_order_item_id,tailor_id,tailor_commission,completion_date,rating,status',
            'items.tailorAssignments.tailor:id,name',
        ])->find($orderId);
    }

    protected function setTailorActionModalData(TailoringOrder $order): void
    {
        $this->selectedTailorOrderDetails = [
            'id' => (int) $order->id,
            'order_no' => (string) $order->order_no,
            'order_date' => $order->order_date ? $order->order_date->format('Y-m-d') : null,
            'customer_name' => $order->account?->name ?? $order->customer_name,
            'customer_mobile' => $order->customer_mobile,
            'status' => (string) ($order->status ?? ''),
        ];

        $this->selectedTailorAssignments = $order->items
            ->sortBy('item_no')
            ->flatMap(function ($item) {
                return $item->tailorAssignments
                    ->sortBy('id')
                    ->map(function ($assignment) use ($item) {
                        return [
                            'assignment_id' => (int) $assignment->id,
                            'item_id' => (int) $item->id,
                            'item_no' => (int) $item->item_no,
                            'product_name' => (string) ($item->product_name ?? ''),
                            'quantity' => (float) $item->quantity,
                            'completion_status' => (string) ($item->completion_status ?? ''),
                            'delivery_status' => (string) ($item->delivery_status ?? ''),
                            'tailor_name' => (string) ($assignment->tailor?->name ?? 'Unassigned'),
                            'tailor_commission' => (float) ($assignment->tailor_commission ?? 0),
                            'completion_date' => $assignment->completion_date ? $assignment->completion_date->format('Y-m-d') : null,
                            'rating' => $assignment->rating !== null ? (int) $assignment->rating : null,
                            'status' => (string) ($assignment->status ?? 'pending'),
                        ];
                    });
            })
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.tailoring.order-tailor-action-modal');
    }
}
