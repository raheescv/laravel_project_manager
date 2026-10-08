<?php

namespace App\Http\Resources\V1\Technician;

use App\Enums\RentOut\ChecklistItemStatus;
use App\Enums\RentOut\ChecklistPhase;
use App\Enums\RentOut\ChecklistSignatoryRole;
use App\Models\RentOut;
use App\Support\RentOutChecklistState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * One hand-over in the technician's checklist inbox: the unit, the lessee, the
 * phase that is open and how far it has got.
 *
 * @mixin RentOut
 */
class ChecklistJobResource extends JsonResource
{
    /** The phase to describe; null means the rent-out's default (open) phase. */
    public function __construct($resource, protected ?ChecklistPhase $phase = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $phase = RentOutChecklistState::resolvePhase($this->resource, $this->phase);
        $statusColumn = $phase->statusColumn();
        $lines = $this->checklistLines;
        $userId = (int) Auth::id();

        $myRoles = collect([
            (int) $this->facility_coordinator_id === $userId ? ChecklistSignatoryRole::FacilityCoordinator : null,
            (int) $this->leasing_coordinator_id === $userId ? ChecklistSignatoryRole::LeasingCoordinator : null,
        ])->filter()->map(fn (ChecklistSignatoryRole $role) => [
            'role' => $role->value,
            'label' => $role->labelFor($this->agreement_type),
        ])->values();

        return [
            'id' => $this->id,
            'unit' => (string) ($this->property->number ?? ''),
            'building' => (string) ($this->property?->building->name ?? ''),
            'group' => (string) ($this->property?->group->name ?? ''),
            'agreement_type' => $this->agreement_type->value,
            'agreement_label' => $this->agreement_type->label(),
            'reference_no' => $this->reference_no,
            'lessee_name' => (string) ($this->account->name ?? ''),
            'lessee_mobile' => (string) ($this->account->mobile ?? ''),
            'phase' => $phase->value,
            'phase_label' => RentOutChecklistState::phaseLabel($this->resource, $phase),
            'phases' => array_map(fn (ChecklistPhase $p) => $p->value, RentOutChecklistState::phasesFor($this->resource)),
            'scheduled_date' => RentOutChecklistState::scheduledDate($this->resource, $phase)?->toDateString(),
            'my_roles' => $myRoles,
            'lines_total' => $lines->count(),
            'lines_checked' => $lines->filter(fn ($line) => $line->{$statusColumn} !== null)->count(),
            'lines_damaged' => $phase === ChecklistPhase::MoveOut
                ? $lines->filter(fn ($line) => $line->move_out_status === ChecklistItemStatus::NotOk)->count()
                : 0,
            'signatures_done' => RentOutChecklistState::signaturesDone($this->resource, $phase),
            'signatures_required' => RentOutChecklistState::signaturesRequired(),
            'ready_to_seal' => RentOutChecklistState::isFullySigned($this->resource, $phase),
            'sealed' => RentOutChecklistState::isSealed($this->resource, $phase),
        ];
    }

    /** Root-relative storage path the app prefixes with its own reachable base URL. */
    public static function storagePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return '/storage/'.ltrim(preg_replace('#^public/#', '', $path), '/');
    }
}
