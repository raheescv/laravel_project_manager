<?php

namespace App\Actions\Maintenance\Complaint\Concerns;

use App\Models\MaintenanceComplaint;
use App\Models\SupplyRequest;

/**
 * Shared supply-request plumbing for the complaint-workflow actions: lazily
 * resolving the complaint's supply request and recomputing its rolled-up
 * totals. Lifted verbatim from App\Livewire\Maintenance\Complaint so the web
 * page and the mobile API run identical logic.
 */
trait ManagesSupplyRequest
{
    /**
     * Get the complaint's supply request, creating it on first use. Mirrors
     * Complaint::getOrCreateSupplyRequest.
     */
    protected function getOrCreateSupplyRequest(MaintenanceComplaint $mc, int $userId): SupplyRequest
    {
        if ($mc->supply_request_id) {
            return SupplyRequest::findOrFail($mc->supply_request_id);
        }

        $maintenance = $mc->maintenance;
        $sr = SupplyRequest::create([
            'tenant_id' => $mc->tenant_id,
            'branch_id' => $mc->branch_id,
            'property_id' => $maintenance->property_id,
            'property_group_id' => $maintenance->property_group_id,
            'property_building_id' => $maintenance->property_building_id,
            'property_type_id' => $maintenance->property_type_id,
            'order_no' => time(),
            'date' => now()->format('Y-m-d'),
            'type' => 'Add',
            'status' => 'requirement',
            'total' => 0,
            'other_charges' => 0,
            'grand_total' => 0,
            'created_by' => $userId,
        ]);

        $mc->supply_request_id = $sr->id;
        $mc->save();

        return $sr;
    }

    /**
     * Recompute a supply request's totals. Mirrors Complaint::updateSupplyTotals
     * (`total` on items is a stored column: quantity × unit_price).
     */
    protected function recalculateSupplyTotals(SupplyRequest $sr): void
    {
        $sr->total = $sr->items()->sum('total');
        $sr->grand_total = $sr->total + ($sr->other_charges ?? 0);
        $sr->save();
    }
}
