<?php

namespace App\Actions\Tailoring\Order;

use App\Models\TailoringOrderItem;
use App\Models\TailoringOrderItemTailor;
use Illuminate\Support\Facades\Auth;

class SyncTailorAssignmentAction
{
    public function execute(TailoringOrderItem $item, array $data): void
    {
        $tailorAssignments = collect($data['tailor_assignments'] ?? ($data['tailor_assignment'] ?? []))
            ->when(isset($data['tailor_assignment']) && is_array($data['tailor_assignment']), function ($collection) use ($data) {
                if (isset($data['tailor_assignments']) && is_array($data['tailor_assignments']) && ! empty($data['tailor_assignments'])) {
                    return $collection;
                }

                return collect([$data['tailor_assignment']]);
            })
            ->filter(fn ($assignment) => is_array($assignment))
            ->values();

        $units = $this->assignmentUnitCount($item, $data);
        if ($tailorAssignments->isEmpty()) {
            $tailorAssignments = collect(range(1, $units))->map(function ($i) use ($data) {
                return $i === 1 ? ['status' => $data['status'] ?? 'pending'] : [];
            });
        }

        if ($tailorAssignments->count() < $units) {
            $tailorAssignments = $tailorAssignments->merge(
                collect(range(1, $units - $tailorAssignments->count()))->map(fn () => [])
            );
        }

        $tailorAssignments = $tailorAssignments->take($units)->values();

        $keptIds = [];
        foreach ($tailorAssignments as $tailorAssignmentData) {
            $tailorAssignmentData = array_merge([
                'tailor_id' => null,
                'tailor_commission' => 0,
                'completion_date' => null,
                'rating' => null,
                'status' => 'pending',
            ], $tailorAssignmentData);

            $assignment = isset($tailorAssignmentData['id']) ? $item->tailorAssignments()->where('id', $tailorAssignmentData['id'])->first() : null;

            if (! $assignment) {
                $assignment = new TailoringOrderItemTailor();
                $assignment->tenant_id = $item->tenant_id;
                $assignment->tailoring_order_item_id = $item->id;
                $assignment->created_by = Auth::id() ?: $item->updated_by;
            }

            $assignment->tailor_id = $tailorAssignmentData['tailor_id'] ?: null;
            $assignment->tailor_commission = (float) ($tailorAssignmentData['tailor_commission'] ?? 0);
            $assignment->completion_date = $tailorAssignmentData['completion_date'] ?: null;
            $assignment->rating = $tailorAssignmentData['rating'] !== null ? max(1, min(5, (int) $tailorAssignmentData['rating'])) : null;
            $assignment->status = in_array($tailorAssignmentData['status'] ?? null, ['pending', 'completed', 'delivered']) ? $tailorAssignmentData['status'] : 'pending';
            $assignment->updated_by = Auth::id() ?: $item->updated_by;
            $assignment->save();

            $keptIds[] = $assignment->id;
        }

        if (! empty($keptIds)) {
            $item->tailorAssignments()->whereNotIn('id', $keptIds)->delete();
        }

        $this->syncItemSummary($item);
    }

    public function syncItemSummary(TailoringOrderItem $item): void
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

    private function assignmentUnitCount(TailoringOrderItem $item, array $data = []): int
    {
        $quantity = isset($data['quantity']) ? (float) $data['quantity'] : (float) $item->quantity;

        return max(1, (int) round($quantity));
    }
}
