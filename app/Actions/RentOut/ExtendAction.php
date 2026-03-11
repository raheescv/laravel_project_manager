<?php

namespace App\Actions\RentOut;

use App\Models\RentOut;
use App\Models\RentOutExtend;

class ExtendAction
{
    public function execute($data)
    {
        try {
            validationHelper(RentOutExtend::rules(), $data, 'RentOut Extension');

            $rentOut = RentOut::find($data['rent_out_id']);
            if (! $rentOut) {
                throw new \Exception('RentOut not found.', 1);
            }

            $data['tenant_id'] = $data['tenant_id'] ?? $rentOut->tenant_id;
            $data['branch_id'] = $data['branch_id'] ?? $rentOut->branch_id;

            $extend = RentOutExtend::create($data);

            // Update end_date on main agreement
            $rentOut->update([
                'end_date' => $data['end_date'],
            ]);

            $return['success'] = true;
            $return['message'] = 'Successfully Extended RentOut Agreement';
            $return['data'] = $extend;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
