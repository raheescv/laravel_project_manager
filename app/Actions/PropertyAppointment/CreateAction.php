<?php

namespace App\Actions\PropertyAppointment;

use App\Models\PropertyAppointment;
use App\Models\RentOut;
use Illuminate\Support\Str;

class CreateAction
{
    public $model;

    public $userId;

    /**
     * Opens a appointment against an agreement.
     *
     * The employee is a CHOICE made on the appointment, not something inherited
     * from the agreement: whoever shows the property is often not whoever owns
     * the lease, and reassigning the agreement later must never move a booking
     * onto a different person's calendar behind their back.
     */
    public function execute($data, $userId)
    {
        $this->userId = $userId;
        try {
            $rentOut = RentOut::findOrFail($data['rent_out_id']);

            if (blank($data['employee_id'] ?? null)) {
                throw new \Exception('Please select the employee who will carry out this appointment.', 1);
            }

            $data['tenant_id'] = $rentOut->tenant_id;
            $data['branch_id'] = $data['branch_id'] ?? $rentOut->branch_id ?? session('branch_id');
            $data['account_id'] = $rentOut->account_id;
            $data['status'] = $data['status'] ?? 'awaiting';
            $data['token'] = (string) Str::uuid();
            $data['token_expires_at'] = $data['token_expires_at'] ?? now()->addDays(14);
            $data['created_by'] = $this->userId;

            validationHelper(PropertyAppointment::rules(), $data);

            $this->model = PropertyAppointment::create($data);

            $return['success'] = true;
            $return['message'] = 'Successfully created appointment';
            $return['data'] = $this->model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
