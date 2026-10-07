<?php

namespace App\Actions\V1\Technician\Checklist;

use App\Actions\V1\Technician\Concerns\InteractsWithChecklist;
use App\Enums\RentOut\ChecklistPhase;
use App\Models\RentOut;
use App\Support\RentOutChecklistState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The technician's hand-over list. Each row is one hand-over *job* — a rent-out
 * they coordinate together with one of its phases — so a rental whose move-in is
 * sealed and whose move-out is due shows up twice once completed jobs are asked
 * for. Open jobs only by default; soonest first.
 *
 * Small by nature (a coordinator's own units), so it is returned whole rather
 * than paginated; the date range keeps the completed history bounded.
 */
class ListAction
{
    use InteractsWithChecklist;

    public const STATUS_OPEN = 'open';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ALL = 'all';

    /**
     * @param  array{search?: ?string, phase?: ?ChecklistPhase, status?: ?string, from_date?: ?string, to_date?: ?string}  $filters
     * @return Collection<int, array{rentOut: RentOut, phase: ChecklistPhase}>
     */
    public function execute(array $filters = []): Collection
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $phase = $filters['phase'] ?? null;
        $status = $filters['status'] ?? self::STATUS_OPEN;
        $from = filled($filters['from_date'] ?? null) ? Carbon::parse($filters['from_date'])->startOfDay() : null;
        $to = filled($filters['to_date'] ?? null) ? Carbon::parse($filters['to_date'])->endOfDay() : null;

        return $this->ownedRentOuts()
            ->with(['account', 'property.building', 'property.group', 'checklistLines:id,rent_out_id,move_in_status,move_out_status', 'checklistSignatures'])
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->whereHas('account', fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%"))
                ->orWhereHas('property', fn (Builder $q) => $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('building', fn (Builder $q) => $q->where('name', 'like', "%{$search}%")))))
            ->get()
            ->flatMap(fn (RentOut $rentOut) => collect($this->jobPhases($rentOut, $status))
                ->map(fn (ChecklistPhase $p) => ['rentOut' => $rentOut, 'phase' => $p]))
            ->filter(function (array $job) use ($phase, $from, $to) {
                if ($phase !== null && $job['phase'] !== $phase) {
                    return false;
                }
                if (! $from && ! $to) {
                    return true;
                }
                $date = RentOutChecklistState::scheduledDate($job['rentOut'], $job['phase']);

                return $date !== null && (! $from || $date->gte($from)) && (! $to || $date->lte($to));
            })
            ->sortBy(fn (array $job) => RentOutChecklistState::scheduledDate($job['rentOut'], $job['phase'])?->timestamp ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * The rent-out's jobs for [status]: the open phase, the sealed ones, or every
     * phase it has reached.
     *
     * @return array<int, ChecklistPhase>
     */
    private function jobPhases(RentOut $rentOut, string $status): array
    {
        $open = RentOutChecklistState::openPhase($rentOut);

        return match ($status) {
            self::STATUS_COMPLETED => array_values(array_filter(RentOutChecklistState::reachedPhases($rentOut), fn (ChecklistPhase $p) => RentOutChecklistState::isSealed($rentOut, $p))),
            self::STATUS_ALL => RentOutChecklistState::reachedPhases($rentOut),
            default => $open ? [$open] : [],
        };
    }
}
