<?php

namespace App\Actions\RentOut;

use App\Models\RentOutExtend;

class UpdateExtendAction
{
    /**
     * Correct a recorded extension. When it is the agreement's latest
     * extension, the agreement end date follows the corrected end date.
     *
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string, data?: RentOutExtend}
     */
    public function execute(array $data, int $id): array
    {
        try {
            $extend = RentOutExtend::find($id);
            if (! $extend) {
                throw new \Exception('Extension not found.', 1);
            }

            $data['rent_out_id'] = $extend->rent_out_id;
            validationHelper(RentOutExtend::rules($id), $data, 'RentOut Extension');

            $isLatest = ! RentOutExtend::where('rent_out_id', $extend->rent_out_id)
                ->where('id', '!=', $extend->id)
                ->where('end_date', '>', $extend->end_date)
                ->exists();

            $extend->update([
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'rent_amount' => $data['rent_amount'],
                'payment_mode' => $data['payment_mode'],
                'remarks' => $data['remarks'] ?? '',
            ]);

            if ($isLatest) {
                $extend->rentOut()->update(['end_date' => $data['end_date']]);
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Extension';
            $return['data'] = $extend;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
