<?php

namespace App\Actions\PropertyAppointment;

use App\Models\PropertyAppointment;
use App\Services\PropertyAppointment\SlotService;

class UpdateAction
{
    public $model;

    public $userId;

    public function execute($data, $id, $userId)
    {
        $this->userId = $userId;
        try {
            $this->model = PropertyAppointment::findOrFail($id);

            $data['updated_by'] = $this->userId;

            validationHelper(PropertyAppointment::rules($id), array_merge($this->model->toArray(), $data));

            $this->guardEmployeeChange($data);

            $this->model->update($data);

            $return['success'] = true;
            $return['message'] = 'Successfully updated appointment';
            $return['data'] = $this->model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * Handing a booked appointment to someone else has to clear THEIR diary.
     *
     * Only the clash is checked, not the whole of windowProblem(): the notice
     * cut-off and the booking horizon are rules for a customer choosing a time,
     * and they would wrongly refuse a manager moving this afternoon's visit to
     * a colleague. The database unique index still owns the final word on two
     * appointments landing on one exact start.
     */
    private function guardEmployeeChange(array $data): void
    {
        $employeeId = (int) ($data['employee_id'] ?? 0);

        if (! $employeeId || $employeeId === (int) $this->model->employee_id) {
            return;
        }

        $start = $this->model->scheduled_at;
        $end = $this->model->endsAt();

        if (! $start || ! $end || ! in_array($this->model->status, PropertyAppointment::HOLDING_STATUSES, true)) {
            return;
        }

        $stretches = app(SlotService::class)
            ->busyStretches($employeeId, $start->copy()->startOfDay(), $start->copy()->endOfDay(), $this->model->id)[$start->toDateString()] ?? [];

        foreach ($stretches as $stretch) {
            $busyStart = $start->copy()->setTimeFromTimeString($stretch['start']);
            $busyEnd = $start->copy()->setTimeFromTimeString($stretch['end']);

            // Half-open ranges: 10:00-11:00 and 11:00-12:00 do not overlap.
            if ($start->lt($busyEnd) && $end->gt($busyStart)) {
                throw new \Exception($stretch['reason'] === 'booked'
                    ? 'That employee already has an appointment at this time. Reschedule it first, or pick someone else.'
                    : 'That employee is unavailable at this time. Reschedule it first, or pick someone else.', 1);
            }
        }
    }
}
