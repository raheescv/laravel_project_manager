<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The company's working week — which days the business opens and the hours it
 * keeps on each of them.
 *
 * This is the GLOBAL answer to "when can customers be seen". A salesman's own
 * PropertyAppointmentAvailability rows override it; when an employee has none,
 * the scheduler falls back to the week described here, so a new employee is
 * bookable from their first day without anyone touching their schedule.
 */
class WorkingDay extends Model
{
    use BelongsToTenant;

    /** Day-of-week index used by PropertyAppointmentAvailability, 0 = Sunday. */
    public const DAY_INDEX = [
        'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
        'thursday' => 4, 'friday' => 5, 'saturday' => 6,
    ];

    protected $fillable = [
        'tenant_id',
        'day_name',
        'is_working',
        'start_time',
        'end_time',
        'order_no',
    ];

    protected $casts = [
        'is_working' => 'boolean',
        'order_no' => 'integer',
    ];

    public function scopeWorking(Builder $query): Builder
    {
        return $query->where('is_working', true);
    }

    /**
     * This row's day-of-week index, or null when day_name is not a weekday.
     *
     * Rows are written in different cases by different callers (the seeder
     * stores 'MONDAY', the settings screen shows 'Monday'), so the name is
     * always normalised before it is matched.
     */
    public function dayIndex(): ?int
    {
        return self::DAY_INDEX[Str::lower(trim((string) $this->day_name))] ?? null;
    }

    /** The hours this day keeps, with the module defaults filling any gap. */
    public function timing(): array
    {
        $defaults = self::moduleDefaults();

        return [
            'start_time' => self::normaliseTime($this->start_time) ?? $defaults['start_time'],
            'end_time' => self::normaliseTime($this->end_time) ?? $defaults['end_time'],
        ];
    }

    /**
     * The company working week, keyed by day-of-week index (0 = Sunday).
     *
     * This is the one place the fallback chain is resolved: a configured row
     * wins, its individual blank columns fall back to the module defaults, and
     * a tenant with no rows at all gets the module default week. An empty array
     * therefore means one thing only — the tenant has deliberately switched
     * every day off.
     *
     * @return array<int, array{start_time: string, end_time: string}>
     */
    public static function schedule(): array
    {
        $configured = self::query()->orderBy('order_no')->get();

        if ($configured->isEmpty()) {
            return self::moduleDefaultSchedule();
        }

        $schedule = [];

        foreach ($configured->where('is_working', true) as $day) {
            $index = $day->dayIndex();
            if ($index === null) {
                continue;
            }

            $schedule[$index] = $day->timing();
        }

        ksort($schedule);

        return $schedule;
    }

    /** Working day indexes only, for callers that do not care about the hours. */
    public static function scheduleDays(): array
    {
        return array_keys(self::schedule());
    }

    /** The opening and closing times from config/property_appointment.php. */
    public static function moduleDefaults(): array
    {
        $defaults = (array) config('property_appointment.default_availability', []);

        return [
            'start_time' => self::normaliseTime($defaults['start_time'] ?? null) ?? '09:00',
            'end_time' => self::normaliseTime($defaults['end_time'] ?? null) ?? '18:00',
        ];
    }

    /** The default week used before a tenant has configured any working day. */
    private static function moduleDefaultSchedule(): array
    {
        $timing = self::moduleDefaults();
        $days = (array) config('property_appointment.default_availability.days', []);

        $schedule = [];
        foreach (array_unique(array_map('intval', $days)) as $index) {
            if ($index >= 0 && $index <= 6) {
                $schedule[$index] = $timing;
            }
        }

        ksort($schedule);

        return $schedule;
    }

    /** 'H:i' for anything time-shaped, null for anything blank. */
    private static function normaliseTime($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::substr($value, 0, 5);
    }
}
