<?php

namespace App\Livewire\Property\Property;

use App\Models\Maintenance;
use App\Models\Property;
use App\Models\RentOut;
use App\Models\RentOutDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Property view — "Command Workspace".
 *
 * Every dataset is a #[Computed] property so it is resolved at most once per
 * request and only when the blade actually reaches for it. Aggregates on the
 * agreements (collected / balance / instalment counts / security held) are
 * pushed into SQL with withSum/withCount instead of loading payment terms.
 *
 * @property-read Property|null $property
 * @property-read Collection $agreements
 * @property-read RentOut|null $live
 * @property-read array<string, mixed> $stats
 * @property-read array<string, mixed>|null $current
 * @property-read Collection $maintenances
 * @property-read Collection $documents
 * @property-read \Illuminate\Support\Collection $activity
 */
class View extends Component
{
    public int $propertyId;

    protected $listeners = ['Property-Refresh-Component' => '$refresh'];

    public function mount(int|string $id): void
    {
        abort_unless(auth()->user()?->can('property.view'), 403);

        $this->propertyId = (int) $id;
    }

    #[Computed]
    public function property(): ?Property
    {
        return Property::with(['group:id,name', 'building:id,name', 'type:id,name'])
            ->find($this->propertyId);
    }

    /** All agreements raised on this unit, newest first, with their money rolled up in SQL. */
    #[Computed]
    public function agreements(): Collection
    {
        return RentOut::query()
            ->where('property_id', $this->propertyId)
            ->with('customer:id,name,mobile,email')
            ->withCount([
                'paymentTerms',
                'paymentTerms as paid_terms_count' => fn ($q) => $q->where('status', 'paid'),
            ])
            ->withSum('paymentTerms as terms_total', 'total')
            ->withSum(['paymentTerms as terms_paid' => fn ($q) => $q->where('status', 'paid')], 'total')
            ->withSum('paymentTerms as terms_balance', 'balance')
            ->withSum('securities as security_total', 'amount')
            ->orderByDesc('start_date')
            ->get();
    }

    /** The agreement the unit is currently let under, if any. */
    #[Computed]
    public function live(): ?RentOut
    {
        return $this->agreements->first(
            fn (RentOut $a) => in_array($a->status?->value, ['occupied', 'booked'], true)
        );
    }

    /**
     * Letting performance for the whole life of the unit: occupied days, vacancy
     * gaps, revenue collected and the year-by-year split, all derived from the
     * one agreements query above — no extra round trips.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function stats(): array
    {
        $today = Carbon::today();
        $timeline = $this->agreements
            ->reject(fn (RentOut $a) => $a->status?->value === 'cancelled')
            ->sortBy('start_date')
            ->values();

        $letDays = 0;
        $gapDays = 0;
        $gapCount = 0;
        $cursor = null;

        foreach ($timeline as $agreement) {
            $start = $agreement->start_date?->copy()->startOfDay();
            $end = ($agreement->vacate_date ?: $agreement->end_date)?->copy()->startOfDay();

            if (! $start || ! $end) {
                continue;
            }

            $end = $end->gt($today) ? $today->copy() : $end;

            if ($end->lte($start)) {
                continue;
            }

            if ($cursor && $start->gt($cursor)) {
                $gapCount++;
                $gapDays += (int) $cursor->diffInDays($start);
            }

            // Clip against the cursor so overlapping agreements are not counted twice.
            $from = $cursor && $cursor->gt($start) ? $cursor : $start;

            if ($end->gt($from)) {
                $letDays += (int) $from->diffInDays($end);
            }

            $cursor = ! $cursor || $end->gt($cursor) ? $end->copy() : $cursor;
        }

        $firstStart = $timeline->first()?->start_date?->copy()->startOfDay();
        $trackedDays = $firstStart ? (int) $firstStart->diffInDays($today) : 0;

        $tenancyMonths = $timeline
            ->map(fn (RentOut $a) => $a->start_date && $a->end_date ? $a->start_date->diffInMonths($a->end_date) : null)
            ->filter();

        return [
            'agreements' => $this->agreements->count(),
            'tenancies' => $timeline->count(),
            'tenants' => $timeline->pluck('account_id')->unique()->count(),
            'collected' => (float) $this->agreements->sum('terms_paid'),
            'outstanding' => (float) $timeline->sum('terms_balance'),
            'let_days' => $letDays,
            'tracked_days' => $trackedDays,
            'occupancy' => $trackedDays > 0 ? min(100, (int) round($letDays / $trackedDays * 100)) : 0,
            'gap_days' => $gapDays,
            'gap_count' => $gapCount,
            'avg_tenancy' => $tenancyMonths->isNotEmpty() ? (int) round($tenancyMonths->avg()) : 0,
            'avg_rent' => $timeline->isNotEmpty() ? (float) $timeline->avg('rent') : 0.0,
            'since' => $firstStart,
        ];
    }

    /** Collection state of the live agreement — drives the hero meter and the tenancy panel. */
    #[Computed]
    public function current(): ?array
    {
        if (! $live = $this->live) {
            return null;
        }

        $today = Carbon::today();
        $start = $live->start_date;
        $end = $live->end_date;
        $span = $start && $end ? (int) $start->diffInDays($end) : 0;
        $done = $start ? max(0, min($span, (int) $start->diffInDays($today))) : 0;
        $net = (float) $live->terms_total;

        return [
            'agreement' => $live,
            'elapsed' => $span > 0 ? (int) round($done / $span * 100) : 0,
            'days_left' => $end ? (int) $today->diffInDays($end, false) : 0,
            'total' => $net,
            'paid' => (float) $live->terms_paid,
            'balance' => (float) $live->terms_balance,
            'security' => (float) $live->security_total,
            'collected_percent' => $net > 0 ? min(100, (int) round($live->terms_paid / $net * 100)) : 0,
        ];
    }

    #[Computed]
    public function maintenances(): Collection
    {
        return Maintenance::query()
            ->where('property_id', $this->propertyId)
            ->with(['rentOut:id,start_date,agreement_type', 'completedBy:id,name'])
            ->orderByDesc('date')
            ->limit(50)
            ->get();
    }

    #[Computed]
    public function documents(): Collection
    {
        $ids = $this->agreements->pluck('id');

        if ($ids->isEmpty()) {
            return new Collection();
        }

        return RentOutDocument::query()
            ->whereIn('rent_out_id', $ids)
            ->with(['documentType:id,name', 'rentOut:id,start_date,agreement_type'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function activity()
    {
        return $this->property?->audits()
            ->with('user:id,name')
            ->latest()
            ->limit(15)
            ->get() ?? collect();
    }

    public function render()
    {
        return view('livewire.property.property.view');
    }
}
