<?php

namespace App\Actions\PropertyAppointment\Availability;

use App\Models\PropertyAppointmentAvailability;
use App\Models\WorkingDay;

/**
 * Fills in a salesman's whole working week in one go.
 *
 * Which days count is decided by the tenant's Settings -> Working Day, so the
 * default week always matches the week the business actually keeps. Only when
 * that has never been configured does it fall back to config/property_appointment.php.
 *
 * It only ever ADDS days that have no rule yet, so pressing the button twice
 * cannot duplicate hours or overwrite times someone has already tuned.
 */
class CreateDefaultsAction
{
    /** Day-of-week index used by PropertyAppointmentAvailability, 0 = Sunday. */
    private const DAY_INDEX = [
        'Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
        'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6,
    ];

    public function execute($userId, $actorId = null)
    {
        try {
            $defaults = config('property_appointment.default_availability', []);
            $days = $this->workingDays($defaults);

            if (! $days) {
                throw new \Exception('No working days are configured, so there is nothing to add. Set them in Settings → Working Day.', 1);
            }

            $existing = PropertyAppointmentAvailability::where('user_id', $userId)
                ->pluck('day_of_week')
                ->map(fn ($day) => (int) $day)
                ->all();

            $created = 0;

            foreach ($days as $dayOfWeek) {
                if (in_array($dayOfWeek, $existing, true)) {
                    continue;
                }

                $payload = [
                    'user_id' => $userId,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $defaults['start_time'] ?? '09:00',
                    'end_time' => $defaults['end_time'] ?? '18:00',
                    'slot_interval_minutes' => $defaults['slot_interval_minutes'] ?? 60,
                ];

                $response = (new CreateAction())->execute($payload, $actorId);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }

                $created++;
            }

            if ($created === 0) {
                throw new \Exception('Every working day already has hours set — nothing to add.', 1);
            }

            $return['success'] = true;
            $return['message'] = 'Added default hours for '.$created.' day'.($created === 1 ? '' : 's')
                .'. Adjust any of them to suit.';
            $return['data'] = ['created' => $created];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * The tenant's configured working days, or the module default when they
     * have never set any.
     *
     * @return array<int, int>
     */
    private function workingDays(array $defaults): array
    {
        $configured = WorkingDay::query()->orderBy('order_no')->get();

        if ($configured->isEmpty()) {
            return array_values(array_unique(array_map('intval', $defaults['days'] ?? [])));
        }

        return $configured
            ->where('is_working', true)
            ->map(fn (WorkingDay $day) => self::DAY_INDEX[ucfirst(strtolower((string) $day->day_name))] ?? null)
            ->filter(fn ($index) => $index !== null)
            ->unique()
            ->values()
            ->all();
    }
}
