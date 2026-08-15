<?php

namespace App\Services\PropertyAppointment;

use App\Models\PropertyAppointment;
use App\Models\PropertyAppointmentAvailability;
use App\Models\PropertyAppointmentTimeOff;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Turns a salesman's weekly availability into concrete bookable slots.
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
     * Bookable slots for one salesman, keyed by Y-m-d.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    public function availableSlots(int $salesmanId, ?Carbon $from = null, ?Carbon $to = null, ?int $ignoreAppointmentId = null): array
    {
        $from = ($from ?? now())->copy()->startOfDay();
        $to = ($to ?? now()->addDays(self::appointmentWindowDays()))->copy()->endOfDay();

        $rules = $this->rules($salesmanId);
        if ($rules->isEmpty()) {
            return [];
        }

        $timeOffs = $this->timeOffs($salesmanId, $from, $to);
        $taken = $this->takenSlots($salesmanId, $from, $to, $ignoreAppointmentId);
        $workingDays = $this->workingDayNames();
        $earliest = now()->addHours(self::minimumNoticeHours());

        $slots = [];

        foreach (CarbonPeriod::create($from, $to) as $day) {
            /** @var Carbon $day */
            if ($workingDays !== null && ! in_array($day->format('l'), $workingDays, true)) {
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
     * Whether one specific slot is still bookable. Used by BookAction as a
     * friendly pre-check — the database unique index is what actually enforces
     * it, because anything checked before an INSERT can go stale mid-request.
     */
    public function isSlotBookable(int $salesmanId, Carbon $slot, ?int $ignoreAppointmentId = null): bool
    {
        $slots = $this->availableSlots($salesmanId, $slot->copy()->startOfDay(), $slot->copy()->endOfDay(), $ignoreAppointmentId);

        return collect($slots[$slot->toDateString()] ?? [])
            ->contains('value', $slot->format('Y-m-d H:i:s'));
    }

    /** Weekly rules grouped by day_of_week. */
    protected function rules(int $salesmanId): Collection
    {
        return PropertyAppointmentAvailability::query()
            ->where('user_id', $salesmanId)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');
    }

    protected function timeOffs(int $salesmanId, Carbon $from, Carbon $to): Collection
    {
        return PropertyAppointmentTimeOff::query()
            ->where('user_id', $salesmanId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (PropertyAppointmentTimeOff $off) => $off->date->toDateString());
    }

    /**
     * Slots already held on this salesman's calendar. Mirrors exactly the
     * statuses in the active_slot_key generated column, so the UI and the
     * database constraint can never disagree about what "taken" means.
     */
    protected function takenSlots(int $salesmanId, Carbon $from, Carbon $to, ?int $ignoreAppointmentId): Collection
    {
        return PropertyAppointment::query()
            ->holdingSlot()
            ->where('salesman_id', $salesmanId)
            ->whereBetween('scheduled_at', [$from, $to])
            ->when($ignoreAppointmentId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->pluck('scheduled_at')
            ->map(fn ($value) => Carbon::parse($value)->format('Y-m-d H:i:s'));
    }

    /**
     * Tenant working days, or null when the tenant has not configured any —
     * in which case the salesman's own weekly rules are the only authority.
     */
    protected function workingDayNames(): ?array
    {
        $days = WorkingDay::query()->where('is_working', true)->pluck('day_name');

        return $days->isEmpty() ? null : $days->all();
    }

    /** @return array<int, Carbon> */
    protected function slotsForRule(Carbon $day, PropertyAppointmentAvailability $rule): array
    {
        $interval = max(5, (int) $rule->slot_interval_minutes);
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
