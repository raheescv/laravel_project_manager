<?php

namespace App\Actions\PropertyAppointment\Availability;

use App\Models\PropertyAppointmentAvailability;
use App\Models\WorkingDay;

/**
 * Copies the company working week onto one salesman in one go.
 *
 * The week — which days and the hours kept on each — comes from Settings ->
 * Working Day, so what lands here is exactly what the scheduler already offers
 * on this salesman's behalf. Pressing it is therefore never a change of hours;
 * it is the step you take before tuning a day for this one person.
 *
 * It only ever ADDS days that have no rule yet, so pressing the button twice
 * cannot duplicate hours or overwrite times someone has already tuned.
 */
class CreateDefaultsAction
{
    public function execute($userId, $actorId = null)
    {
        try {
            $schedule = WorkingDay::schedule();

            if (! $schedule) {
                throw new \Exception('No working days are configured, so there is nothing to add. Set them in Settings → Working Day.', 1);
            }

            $existing = PropertyAppointmentAvailability::where('user_id', $userId)
                ->pluck('day_of_week')
                ->map(fn ($day) => (int) $day)
                ->all();

            $created = 0;

            foreach ($schedule as $dayOfWeek => $timing) {
                if (in_array($dayOfWeek, $existing, true)) {
                    continue;
                }

                $payload = [
                    'user_id' => $userId,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $timing['start_time'],
                    'end_time' => $timing['end_time'],
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
            $return['message'] = 'Added the company hours for '.$created.' day'.($created === 1 ? '' : 's')
                .'. Adjust any of them to suit.';
            $return['data'] = ['created' => $created];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
