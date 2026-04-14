<?php

namespace App\Actions\RentOut;

use App\Enums\Property\PropertyStatus;
use App\Enums\RentOut\ChequeStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Models\RentOut;
use App\Models\RentOutCheque;
use App\Models\RentOutPaymentTerm;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VacateAction
{
    public function execute($id, $vacateDate = null)
    {
        try {
            DB::beginTransaction();

            $model = RentOut::find($id);
            if (! $model) {
                throw new \Exception("RentOut not found with the specified ID: $id.");
            }

            if ($vacateDate) {
                $vacateDate = Carbon::parse($vacateDate)->toDateString();

                // Validate vacate date is within agreement range
                if ($vacateDate > $model->end_date->toDateString() || $vacateDate < $model->start_date->toDateString()) {
                    throw new \Exception('Please select a date within the agreement period.');
                }

                // If vacate date is today or in the past, process immediately
                if ($vacateDate <= now()->toDateString()) {
                    // Free up the property
                    $property = $model->property;
                    if ($property) {
                        $property->update([
                            'status' => PropertyStatus::Vacant->value,
                            'availability_status' => 'available',
                        ]);
                    }

                    $model->status = RentOutStatus::Vacated;
                    $model->end_date = $vacateDate;

                    // Clear payment terms after vacate date
                    RentOutPaymentTerm::where('rent_out_id', $model->id)
                        ->where('due_date', '>', Carbon::parse($vacateDate)->addMonth()->toDateString())
                        ->where('status', '!=', 'paid')
                        ->update([
                            'amount' => 0,
                            'discount' => 0,
                            'total' => 0,
                            'balance' => 0,
                            'remarks' => 'Vacated',
                        ]);

                    // Terminate cheques after vacate date
                    RentOutCheque::where('rent_out_id', $model->id)
                        ->where('date', '>', Carbon::parse($vacateDate)->subMonth()->toDateString())
                        ->where('status', '!=', ChequeStatus::Cleared)
                        ->update([
                            'status' => ChequeStatus::Terminated,
                        ]);
                }

                $model->vacate_date = $vacateDate;
            } else {
                $model->vacate_date = null;
            }

            $model->save();

            DB::commit();

            $return['success'] = true;
            $return['message'] = 'Vacate date has been updated successfully.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            DB::rollback();
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
