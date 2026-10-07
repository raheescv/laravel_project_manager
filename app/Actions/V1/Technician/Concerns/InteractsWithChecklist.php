<?php

namespace App\Actions\V1\Technician\Concerns;

use App\Enums\RentOut\ChecklistPhase;
use App\Enums\RentOut\ChecklistSignatoryRole;
use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use App\Models\RentOutFixtureEntry;
use App\Support\RentOutChecklistState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Scopes the hand-over checklist endpoints to the rent-outs the user coordinates
 * (facility or leasing coordinator) that have a checklist to work through. Being
 * one of those coordinators is the only authorisation, as assignment is for
 * complaints.
 */
trait InteractsWithChecklist
{
    use RunsSharedActions;

    /** @return array<int, string> */
    protected function detailRelations(): array
    {
        return [
            'account',
            'property.building',
            'property.group',
            'facilityCoordinator',
            'leasingCoordinator',
            'checklistLines.item',
            'checklistSignatures',
            'fixtureAreas.entries',
        ];
    }

    protected function ownedRentOuts(): Builder
    {
        $userId = Auth::id();

        return RentOut::query()
            ->where(fn (Builder $q) => $q->where('facility_coordinator_id', $userId)->orWhere('leasing_coordinator_id', $userId))
            ->where(fn (Builder $q) => $q->whereNull('status')->orWhere('status', '!=', RentOutStatus::Cancelled->value))
            ->whereHas('checklistLines');
    }

    protected function findOwnedRentOut(int $id): RentOut
    {
        return $this->ownedRentOuts()->findOrFail($id);
    }

    /** The rent-out, provided the user coordinates it and it has [phase] (a lease/sale has no move-out). */
    protected function findOwnedRentOutFor(int $id, ChecklistPhase $phase): RentOut
    {
        $rentOut = $this->findOwnedRentOut($id);

        if (! RentOutChecklistState::hasPhase($rentOut, $phase)) {
            throw ValidationException::withMessages([
                'phase' => 'This agreement is handed over once — it has no move-out checklist.',
            ]);
        }

        return $rentOut;
    }

    protected function findOwnedRentOutWithDetail(int $id): RentOut
    {
        return $this->ownedRentOuts()->with($this->detailRelations())->findOrFail($id);
    }

    /** The fixture entry, provided it sits on a rent-out this user coordinates (404 otherwise). */
    protected function findOwnedFixtureEntry(int $entryId): RentOutFixtureEntry
    {
        return RentOutFixtureEntry::query()
            ->whereHas('area', fn (Builder $q) => $q->whereIn('rent_out_id', $this->ownedRentOuts()->select('id')))
            ->with('area')
            ->findOrFail($entryId);
    }

    /**
     * The signatory roles this user holds on the rent-out.
     *
     * @return array<int, ChecklistSignatoryRole>
     */
    protected function rolesOf(RentOut $rentOut): array
    {
        $userId = (int) Auth::id();

        return array_values(array_filter([
            (int) $rentOut->facility_coordinator_id === $userId ? ChecklistSignatoryRole::FacilityCoordinator : null,
            (int) $rentOut->leasing_coordinator_id === $userId ? ChecklistSignatoryRole::LeasingCoordinator : null,
        ]));
    }
}
