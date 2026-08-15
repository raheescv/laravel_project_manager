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
     * Opens a appointment against an agreement. The salesman is NOT chosen here —
     * it is copied from rent_outs.salesman_id so that reassigning the agreement
     * later cannot silently move historical appointments to someone else's calendar.
     */
    public function execute($data, $userId)
    {
        $this->userId = $userId;
        try {
            $rentOut = RentOut::findOrFail($data['rent_out_id']);

            if (blank($rentOut->salesman_id)) {
                throw new \Exception('This agreement has no salesman assigned, so there is no availability to offer. Assign a salesman first.', 1);
            }

            $data['tenant_id'] = $rentOut->tenant_id;
            $data['branch_id'] = $data['branch_id'] ?? $rentOut->branch_id ?? session('branch_id');
            $data['account_id'] = $rentOut->account_id;
            $data['salesman_id'] = $rentOut->salesman_id;
            $data['status'] = $data['status'] ?? 'awaiting';
            $data['token'] = (string) Str::uuid();
            $data['token_expires_at'] = $data['token_expires_at'] ?? now()->addDays(14);
            $data['reference_no'] = $this->referenceNo();
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

    private function referenceNo(): string
    {
        $next = getNextUniqueNumber('PropertyAppointment');

        return 'VW-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
