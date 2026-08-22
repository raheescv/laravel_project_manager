<?php

namespace App\Services\PropertyAppointment;

use App\Models\PropertyAppointment;
use App\Models\PropertyAppointmentAvailability;
use App\Models\PropertyAppointmentTimeOff;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Turns an employee's weekly availability into concrete bookable slots.
 *
 * Slots are COMPUTED, never stored. Materialising them would mean a nightly
 * generation job, drift whenever a rule is edited, and a backfill every time
 * the appointment window moves — for a grid this small the arithmetic is cheaper
 * and always correct. The uniqueness guarantee does not depend on slot rows
 * existing: it lives on property_appointments.active_slot_key.
 *
 * Every datetime here is in the application timezone (config('app.timezone')).
 * The customer's timezone is a DISPLAY concern handled in the view layer —
 * note that .env declares APP_TIMEZONE twice and the later value wins, so the
 * app timezone is whatever config() reports, not what line 7 of .env says.
 */
class SlotService
{
    /** Fallbacks used when config/property_appointment.php is absent. */
    public const APPOINTMENT_WINDOW_DAYS = 30;

    public const MINIMUM_NOTICE_HOURS = 4;

    public const SLOT_LENGTH_MINUTES = 120;

    /** How many days ahead a customer may book. */
    public static function appointmentWindowDays(): int
    {
        return (int) config('property_appointment.appointment_window_days', self::APPOINTMENT_WINDOW_DAYS);
    }

    /** How much notice a slot needs before it can be taken. */
    public static function minimumNoticeHours(): int
    {
        return (int) config('property_appointment.minimum_notice_hours', self::MINIMUM_NOTICE_HOURS);
    }

    /**
     * How long one appointment runs, in minutes.
     *
     * One number for the whole application, read from config wherever a grid is
     * drawn or a finish time is printed. It is deliberately not stored: as data
     * it was seven identical copies per employee and a question the UI had to
     * ask before anyone could add an hour to a schedule.
     */
    public static function slotLengthMinutes(): int
    {
        return max(5, (int) config('property_appointment.default_availability.slot_interval_minutes', self::SLOT_LENGTH_MINUTES));
    }

    /**
     * Bookable slots for one employee, keyed by Y-m-d.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public function availableSlots(int $employeeId, ?Carbon $from = null, ?Carbon $to = null, ?int $ignoreAppointmentId = null): array
    {
        $from = ($from ?? now())->copy()->startOfDay();
        $to = ($to ?? now()->addDays(self::appointmentWindowDays()))->copy()->endOfDay();

        $rules = $this->rules($employeeId);
        if ($rules->isEmpty()) {
            return [];
        }

        $timeOffs = $this->timeOffs($employeeId, $from, $to);
        $taken = $this->takenSlots($employeeId, $from, $to, $ignoreAppointmentId);
        $workingDays = $this->workingDayIndexes();
        $earliest = now()->addHours(self::minimumNoticeHours());

        $slots = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            /** @var Carbon $day */
            if ($workingDays !== null && ! in_array((int) $day->dayOfWeek, $workingDays, true)) {
                continue;
            }

            $dayRules = $rules->get((int) $day->dayOfWeek);
            if (! $dayRules) {
                continue;
            }

            $dayOffs = $timeOffs->get($day->toDateString(), collect());
            if ($dayOffs->contains(fn (PropertyAppointmentTimeOff $off) => $off->isFullDay())) {
                continue;
            }

            $dayKey = $day->toDateString();

            foreach ($dayRules as $rule) {
                foreach ($this->slotsForRule($day, $rule) as $slot) {
                    if ($slot->lt($earliest)) {
                        continue;
                    }
                    if ($taken->contains($slot->format('Y-m-d H:i:s'))) {
                        continue;
                    }
                    if ($this->blockedByTimeOff($slot, $dayOffs)) {
                        continue;
                    }

                    $slots[$dayKey][] = [
                        // `value` stays a canonical machine timestamp — only the
                        // label follows the tenant's display preference.
                        'value' => $slot->format('Y-m-d H:i:s'),
                        'label' => appointmentTime($slot),
                    ];
                }
            }

            if (isset($slots[$dayKey])) {
                $slots[$dayKey] = collect($slots[$dayKey])->unique('value')->sortBy('value')->values()->all();
            }
        }

        return $slots;
    }

    /**
     * The open hours for each bookable day, keyed by Y-m-d.
     *
     * The customer types a time rather than choosing from the grid, so the page
     * has to know the edges of each day — this is what the slots are generated
     * FROM, before anything is taken out of them.
     *
     * @return array<string, array{start: string, end: string}>
     */
    public function openWindows(int $employeeId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = ($from ?? now())->copy()->startOfDay();
        $to = ($to ?? now()->addDays(self::appointmentWindowDays()))->copy()->endOfDay();

        $rules = $this->rules($employeeId);
        if ($rules->isEmpty()) {
            return [];
        }

        $workingDays = $this->workingDayIndexes();
        $windows = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            /** @var Carbon $day */
            if ($workingDays !== null && ! in_array((int) $day->dayOfWeek, $workingDays, true)) {
                continue;
            }

            $dayRules = $rules->get((int) $day->dayOfWeek);
            if (! $dayRules) {
                continue;
            }

            // A day split across several rules is described by its outer edges;
            // anything punched out of the middle arrives as a busy stretch, so
            // the customer still cannot type their way into a gap that is closed.
            $windows[$day->toDateString()] = [
                'start' => Str::substr(collect($dayRules)->min('start_time'), 0, 5),
                'end' => Str::substr(collect($dayRules)->max('end_time'), 0, 5),
            ];
        }

        return $windows;
    }

    /**
     * Stretches of each day the customer cannot have, keyed by Y-m-d.
     *
     * Two sources, one shape: appointments already on the calendar, and the
     * employee's time off. Both are half-open [start, end) ranges in HH:MM, so
     * a window that ends exactly when a booking starts is fine.
     *
     * @return array<string, array<int, array{start: string, end: string, reason: string}>>
     */
    public function busyStretches(int $employeeId, ?Carbon $from = null, ?Carbon $to = null, ?int $ignoreAppointmentId = null): array
    {
        $from = ($from ?? now())->copy()->startOfDay();
        $to = ($to ?? now()->addDays(self::appointmentWindowDays()))->copy()->endOfDay();

        $busy = [];

        $appointments = PropertyAppointment::query()
            ->holdingSlot()
            ->where('employee_id', $employeeId)
            ->whereBetween('scheduled_at', [$from, $to])
            ->when($ignoreAppointmentId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->get(['id', 'scheduled_at', 'ends_at']);

        foreach ($appointments as $appointment) {
            $start = $appointment->scheduled_at;
            $end = $appointment->endsAt();

            $busy[$start->toDateString()][] = [
                'start' => $start->format('H:i'),
                // An appointment running past midnight is clamped to the day it
                // starts on: the grid never offers times after closing anyway.
                'end' => $end->isSameDay($start) ? $end->format('H:i') : '23:59',
                'reason' => 'booked',
            ];
        }

        foreach ($this->timeOffs($employeeId, $from, $to) as $date => $offs) {
            foreach ($offs as $off) {
                $busy[$date][] = [
                    'start' => $off->isFullDay() ? '00:00' : Str::substr($off->start_time, 0, 5),
                    'end' => $off->isFullDay() ? '23:59' : Str::substr($off->end_time, 0, 5),
                    'reason' => 'unavailable',
                ];
            }
        }

        return $busy;
    }

    /**
     * Whether a typed window can be booked, and why not when it cannot.
     *
     * This is the single gate every booking goes through — a tapped preset is
     * just a window someone filled in for the customer, so slots and typed
     * times cannot drift apart in what they allow. Like isSlotBookable() it is
     * a friendly pre-check: the database still owns the final word on two
     * customers reaching for the same start.
     *
     * @return array{ok: bool, reason: ?string, taken: bool}
     */
    public function windowProblem(int $employeeId, Carbon $start, Carbon $end, ?int $ignoreAppointmentId = null): array
    {
        $fail = fn (string $reason, bool $taken = false) => ['ok' => false, 'reason' => $reason, 'taken' => $taken];

        if (! $end->gt($start)) {
            return $fail('The leaving time has to be after the arriving time.');
        }

        if (! $end->isSameDay($start)) {
            return $fail('An appointment has to start and finish on the same day.');
        }

        if ($start->lt(now()->addHours(self::minimumNoticeHours()))) {
            return $fail('We need at least '.self::minimumNoticeHours().' hours\' notice. Please choose a later time.');
        }

        if ($start->gt(now()->addDays(self::appointmentWindowDays())->endOfDay())) {
            return $fail('That date is too far ahead. Please choose a time within the next '.self::appointmentWindowDays().' days.');
        }

        $window = $this->openWindows($employeeId, $start->copy()->startOfDay(), $start->copy()->endOfDay())[$start->toDateString()] ?? null;

        if (! $window) {
            return $fail('We are closed that day. Please choose another date.');
        }

        $opens = $start->copy()->setTimeFromTimeString($window['start']);
        $closes = $start->copy()->setTimeFromTimeString($window['end']);

        if ($start->lt($opens) || $end->gt($closes)) {
            return $fail('That day runs '.$window['start'].' to '.$window['end'].'. Please choose a time inside those hours.');
        }

        $stretches = $this->busyStretches($employeeId, $start->copy()->startOfDay(), $start->copy()->endOfDay(), $ignoreAppointmentId)[$start->toDateString()] ?? [];

        foreach ($stretches as $stretch) {
            $busyStart = $start->copy()->setTimeFromTimeString($stretch['start']);
            $busyEnd = $start->copy()->setTimeFromTimeString($stretch['end']);

            // Half-open ranges: 10:00-11:00 and 11:00-12:00 do not overlap.
            if ($start->lt($busyEnd) && $end->gt($busyStart)) {
                return $stretch['reason'] === 'booked'
                    ? $fail('That time has just been taken. Please choose another.', true)
                    : $fail('The agent is unavailable then. Please choose another time.');
            }
        }

        return ['ok' => true, 'reason' => null, 'taken' => false];
    }

    /**
     * Whether one specific slot is still bookable. Used by BookAction as a
     * friendly pre-check — the database unique index is what actually enforces
     * it, because anything checked before an INSERT can go stale mid-request.
     */
    public function isSlotBookable(int $employeeId, Carbon $slot, ?int $ignoreAppointmentId = null): bool
    {
        $slots = $this->availableSlots($employeeId, $slot->copy()->startOfDay(), $slot->copy()->endOfDay(), $ignoreAppointmentId);

        return collect($slots[$slot->toDateString()] ?? [])
            ->contains('value', $slot->format('Y-m-d H:i:s'));
    }

    /**
     * Weekly rules grouped by day_of_week.
     *
     * An employee's own rows are an OVERRIDE of the company week, not a
     * prerequisite for being bookable: when they have none, the hours from
     * Settings -> Working Day answer instead, so an employee nobody has given a
     * schedule to still offers the times the business actually keeps.
     */
    protected function rules(int $employeeId): Collection
    {
        $own = PropertyAppointmentAvailability::query()
            ->where('user_id', $employeeId)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return $own->isEmpty() ? $this->companyRules($employeeId) : $own;
    }

    /**
     * The company working week expressed as availability rules.
     *
     * The rules are built in memory and never saved — persisting them would
     * freeze today's company hours onto every employee, so a later change in
     * Settings would silently stop applying to the people who never had their
     * own schedule. Computing them on read keeps the setting live.
     */
    protected function companyRules(int $employeeId): Collection
    {
        $rules = [];

        foreach (WorkingDay::schedule() as $dayOfWeek => $timing) {
            $rules[] = new PropertyAppointmentAvailability([
                'user_id' => $employeeId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $timing['start_time'],
                'end_time' => $timing['end_time'],
                'is_active' => true,
            ]);
        }

        return collect($rules)->groupBy('day_of_week');
    }

    protected function timeOffs(int $employeeId, Carbon $from, Carbon $to): Collection
    {
        return PropertyAppointmentTimeOff::query()
            ->where('user_id', $employeeId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (PropertyAppointmentTimeOff $off) => $off->date->toDateString());
    }

    /**
     * Slots already held on this employee's calendar. Mirrors exactly the
     * statuses in the active_slot_key generated column, so the UI and the
     * database constraint can never disagree about what "taken" means.
     */
    protected function takenSlots(int $employeeId, Carbon $from, Carbon $to, ?int $ignoreAppointmentId): Collection
    {
        return PropertyAppointment::query()
            ->holdingSlot()
            ->where('employee_id', $employeeId)
            ->whereBetween('scheduled_at', [$from, $to])
            ->when($ignoreAppointmentId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->pluck('scheduled_at')
            ->map(fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'));
    }

    /**
     * Tenant working days as day-of-week indexes, or null when the tenant has
     * not configured any — in which case the employee's own weekly rules are the
     * only authority.
     *
     * Indexes rather than names because day_name is stored in whatever case the
     * writer used ('MONDAY' from the seeder, 'Monday' from a test), and matching
     * those strings against Carbon's format('l') silently excluded every day.
     *
     * @return array<int, int>|null
     */
    protected function workingDayIndexes(): ?array
    {
        $days = WorkingDay::query()->get();

        if ($days->isEmpty()) {
            return null;
        }

        return $days->where('is_working', true)
            ->map(fn (WorkingDay $day) => $day->dayIndex())
            ->filter(fn ($index) => $index !== null)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, Carbon> */
    protected function slotsForRule(Carbon $day, PropertyAppointmentAvailability $rule): array
    {
        $interval = self::slotLengthMinutes();
        $cursor = $day->copy()->setTimeFromTimeString($rule->start_time);
        $end = $day->copy()->setTimeFromTimeString($rule->end_time);

        $slots = [];
        while ($cursor->lt($end)) {
            $slots[] = $cursor->copy();
            $cursor->addMinutes($interval);
        }

        return $slots;
    }

    protected function blockedByTimeOff(Carbon $slot, Collection $dayOffs): bool
    {
        return $dayOffs->contains(function (PropertyAppointmentTimeOff $off) use ($slot) {
            if ($off->isFullDay()) {
                return true;
            }

            $start = $slot->copy()->setTimeFromTimeString($off->start_time);
            $end = $slot->copy()->setTimeFromTimeString($off->end_time);

            return $slot->gte($start) && $slot->lt($end);
        });
    }
}
