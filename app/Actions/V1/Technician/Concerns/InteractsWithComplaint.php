<?php

namespace App\Actions\V1\Technician\Concerns;

use App\Models\MaintenanceComplaint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Technician-only helpers: scope every operation to complaints assigned to the
 * authenticated technician (technician_id = auth id) and re-fetch the detail
 * payload. The actual mutation logic is delegated to the shared
 * App\Actions\Maintenance\Complaint\* actions (which the web page also calls).
 */
trait InteractsWithComplaint
{
    /**
     * The eager loads the detail payload (loadData) needs.
     *
     * @return array<int, string>
     */
    protected function detailRelations(): array
    {
        return [
            'maintenance.property.building.group',
            'maintenance.property.type',
            'maintenance.rentOut.customer',
            'maintenance.customer',
            'maintenance.maintenanceComplaints.complaint.category',
            'maintenance.maintenanceComplaints.technician',
            'complaint.category',
            'technician',
            'assignedBy',
            'completedBy',
            'creator',
            'supplyRequest.items.product',
            'supplyRequest.items.branch',
            'supplyRequest.notes.creator',
            'supplyRequest.images',
        ];
    }

    /**
     * Base query scoped to complaints assigned to the authenticated technician.
     */
    protected function ownedComplaints(): Builder
    {
        return MaintenanceComplaint::query()->where('technician_id', Auth::id());
    }

    /**
     * Resolve a complaint the technician owns, or fail (404).
     */
    protected function findOwnedComplaint(int $id): MaintenanceComplaint
    {
        return $this->ownedComplaints()->findOrFail($id);
    }

    /**
     * Fetch a complaint (owned) with every detail relation loaded.
     */
    protected function findOwnedComplaintWithDetail(int $id): MaintenanceComplaint
    {
        return $this->ownedComplaints()->with($this->detailRelations())->findOrFail($id);
    }

    /**
     * Resolve the owned complaint that carries the given supply request, or fail
     * (404 if it isn't the technician's). Used to scope item/note/image
     * sub-resources and to know which complaint's detail to re-fetch.
     */
    protected function findOwnedComplaintBySupplyRequest(int $supplyRequestId): MaintenanceComplaint
    {
        return $this->ownedComplaints()->where('supply_request_id', $supplyRequestId)->firstOrFail();
    }

    /**
     * Unwrap a shared action's `['success','message','data']` result, throwing
     * on failure so the ApiLog records `failed` and the controller responds 422.
     *
     * @param  array<string, mixed>  $result
     * @return mixed the shared action's `data`
     */
    protected function runShared(array $result): mixed
    {
        if (! ($result['success'] ?? false)) {
            throw new \RuntimeException($result['message'] ?? 'Operation failed.');
        }

        return $result['data'] ?? null;
    }
}
