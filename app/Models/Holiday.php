<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * A company holiday — one date the business is closed.
 *
 * This is the GLOBAL calendar the appointment scheduler consults after the
 * working week: a day can be a working Monday and still be shut because it is
 * National Day. Nothing is bookable on a holiday, for anybody, so the slot
 * grid, the typed-time check and the console calendar all read from here.
 *
 * @see WorkingDay for the weekly pattern this overrides.
 */
class Holiday extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'date',
        'is_recurring',
        'is_active',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function rules($id = 0, $merge = []): array
    {
        return array_merge([
            'name' => 'required|string|max:120',
            'date' => 'required|date',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
            'note' => 'nullable|string|max:190',
        ], $merge);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** 'Fri 18 Dec 2026', or year-less when it comes round every year. */
    public function label(): string
    {
        return $this->date->format($this->is_recurring ? 'D d M' : 'D d M Y');
    }

    /**
     * Every closed date between two moments, keyed by Y-m-d.
     *
     * Recurring rows are expanded across each year the range touches, so a
     * calendar looking at next December still sees a National Day entered years
     * ago. The value is the holiday's name because every caller that wants a
     * date also wants to say WHY it is closed.
     *
     * @return array<string, string>
     */
    public static function datesBetween(Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        // A recurring row's own date may sit years before the range, so it
        // cannot be filtered by date in SQL — only the fixed ones can.
        $holidays = self::query()
            ->active()
            ->where(function (Builder $query) use ($from, $to): void {
                $query->where('is_recurring', true)
                    ->orWhereBetween('date', [$from->toDateString(), $to->toDateString()]);
            })
            ->orderBy('date')
            ->get();

        $dates = [];

        foreach ($holidays as $holiday) {
            foreach ($holiday->occurrencesBetween($from, $to) as $date) {
                // Two holidays on one date: the first one entered names the day.
                $dates[$date] ??= $holiday->name;
            }
        }

        ksort($dates);

        return $dates;
    }

    /** The name of the holiday falling on this date, or null when it is open. */
    public static function nameOn(Carbon $date): ?string
    {
        return self::datesBetween($date, $date)[$date->toDateString()] ?? null;
    }

    public static function isOn(Carbon $date): bool
    {
        return self::nameOn($date) !== null;
    }

    /**
     * The Y-m-d dates this one holiday closes inside the range — at most one
     * for a fixed date, one per year for a recurring one.
     *
     * @return array<int, string>
     */
    public function occurrencesBetween(Carbon $from, Carbon $to): array
    {
        if (! $this->is_recurring) {
            return $this->date->betweenIncluded($from, $to) ? [$this->date->toDateString()] : [];
        }

        $dates = [];

        for ($year = $from->year; $year <= $to->year; $year++) {
            $occurrence = $this->occurrenceIn($year);

            if ($occurrence->betweenIncluded($from, $to)) {
                $dates[] = $occurrence->toDateString();
            }
        }

        return $dates;
    }

    /**
     * The date this holiday falls on in a given year — its own date when it is
     * a one-off, the same month and day when it comes round every year.
     *
     * The day is clamped rather than overflowed: Carbon rolls 29 February in a
     * common year forward to 1 March, but a holiday entered on the 29th belongs
     * at the end of February, not in the next month.
     */
    public function occurrenceIn(int $year): Carbon
    {
        if (! $this->is_recurring) {
            return $this->date->copy();
        }

        $endOfMonth = Carbon::create($year, (int) $this->date->month, 1)->endOfMonth();

        return $endOfMonth->copy()->setDay(min((int) $this->date->day, (int) $endOfMonth->day))->startOfDay();
    }
}
