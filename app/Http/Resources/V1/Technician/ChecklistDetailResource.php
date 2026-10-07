<?php

namespace App\Http\Resources\V1\Technician;

use App\Enums\RentOut\ChecklistItemStatus;
use App\Enums\RentOut\ChecklistPhase;
use App\Enums\RentOut\ChecklistSignatoryRole;
use App\Enums\RentOut\FixtureStatus;
use App\Models\RentOut;
use App\Models\RentOutChecklistLine;
use App\Models\RentOutFixtureArea;
use App\Support\RentOutChecklistState;
use Illuminate\Http\Request;

/**
 * Everything the technician's checklist screens need for one phase of a
 * hand-over: the items, the per-area fixture comments and the three signatures.
 * Mirrors what App\Livewire\RentOut\Tabs\ChecklistTab loads.
 *
 * @mixin RentOut
 */
class ChecklistDetailResource extends ChecklistJobResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);
        $phase = ChecklistPhase::from($base['phase']);
        $lines = $this->checklistLines;

        return [
            ...$base,
            'actual_date' => $this->{RentOutChecklistState::handoverDateColumn($this->resource, $phase)}?->toDateString(),
            'remarks' => $this->{$phase->remarksColumn()},
            'damage_total' => $phase === ChecklistPhase::MoveOut
                ? round((float) $lines->filter(fn ($l) => $l->move_out_status === ChecklistItemStatus::NotOk)->sum('damage_cost'), 2)
                : 0.0,
            'lines' => $lines->map(fn (RentOutChecklistLine $line) => $this->line($line))->values(),
            'fixtures' => $this->fixtures($lines),
            'signatures' => $this->signatures($phase, collect($base['my_roles'])->pluck('role')->all()),
        ];
    }

    /** @return array<string, mixed> */
    private function line(RentOutChecklistLine $line): array
    {
        return [
            'id' => $line->id,
            'checklist_id' => $line->checklist_id,
            'name' => (string) ($line->item?->name ?? ''),
            'category' => $line->item?->category ?: 'Others',
            'qty' => $line->qty,
            'sort_order' => $line->sort_order,
            'reference_image' => self::storagePath($line->item?->image_path),
            'move_in_image' => self::storagePath($line->image_path),
            'move_out_image' => self::storagePath($line->move_out_image_path),
            'move_in_status' => $line->move_in_status?->value,
            'move_in_comment' => $line->move_in_comment,
            'move_out_status' => $line->move_out_status?->value,
            'move_out_comment' => $line->move_out_comment,
            'damage_cost' => (float) $line->damage_cost,
        ];
    }

    /**
     * One block per checklist category in item order, then any hand-added area —
     * the same set ChecklistTab::loadFixtures shows. A block nothing has been
     * recorded in yet has a null id.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fixtures($lines): array
    {
        $stored = $this->fixtureAreas->keyBy('category');

        return collect($lines)
            ->map(fn (RentOutChecklistLine $line) => $line->item?->category ?: 'Others')
            ->merge($stored->keys())
            ->unique()
            ->values()
            ->map(function (string $category) use ($stored) {
                /** @var RentOutFixtureArea|null $area */
                $area = $stored->get($category);

                return [
                    'id' => $area?->id,
                    'category' => $category,
                    'owner_name' => $area?->owner_name,
                    'owner_signed_at' => $area?->owner_signed_at?->toIso8601String(),
                    'owner_signature' => self::storagePath($area?->owner_signature_path),
                    'ready_for_acceptance' => (bool) $area?->isReadyForAcceptance(),
                    'entries' => collect($area?->entries ?? [])->map(fn ($entry) => [
                        'id' => $entry->id,
                        'comments' => $entry->comments,
                        'status' => ($entry->status ?? FixtureStatus::Pending)->value,
                        'status_label' => ($entry->status ?? FixtureStatus::Pending)->label(),
                        'completed_date' => $entry->completed_date?->toDateString(),
                        'before_image' => self::storagePath($entry->before_image_path),
                        'after_image' => self::storagePath($entry->after_image_path),
                    ])->values()->all(),
                ];
            })
            ->all();
    }

    /**
     * The three signatories of the phase, in a fixed order.
     *
     * @param  array<int, string>  $myRoles
     * @return array<int, array<string, mixed>>
     */
    private function signatures(ChecklistPhase $phase, array $myRoles): array
    {
        $assignees = [
            ChecklistSignatoryRole::FacilityCoordinator->value => $this->facilityCoordinator?->name,
            ChecklistSignatoryRole::Lessee->value => $this->account?->name,
            ChecklistSignatoryRole::LeasingCoordinator->value => $this->leasingCoordinator?->name,
        ];

        return collect([ChecklistSignatoryRole::FacilityCoordinator, ChecklistSignatoryRole::Lessee, ChecklistSignatoryRole::LeasingCoordinator])
            ->map(function (ChecklistSignatoryRole $role) use ($phase, $myRoles, $assignees) {
                $signature = $this->resource->checklistSignatureFor($phase, $role);
                $isMe = in_array($role->value, $myRoles, true);

                return [
                    'role' => $role->value,
                    'label' => $role->labelFor($this->agreement_type),
                    'assignee_name' => (string) ($assignees[$role->value] ?? ''),
                    'can_sign' => $isMe || $role === ChecklistSignatoryRole::Lessee,
                    'is_me' => $isMe,
                    'signed' => (bool) $signature?->signature_path,
                    'signer_name' => $signature?->signer_name,
                    'signed_at' => $signature?->signed_at?->toIso8601String(),
                    'signature' => self::storagePath($signature?->signature_path),
                ];
            })
            ->all();
    }
}
