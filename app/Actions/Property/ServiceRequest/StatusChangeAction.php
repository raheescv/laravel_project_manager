<?php

namespace App\Actions\Property\ServiceRequest;

use App\Actions\RentOut\Utility\Term\CreateAction as UtilityTermCreateAction;
use App\Enums\Property\ServiceRequestStatus;
use App\Enums\Property\ServiceRequestType;
use App\Models\PropertyServiceRequest;
use App\Models\RentOut;
use App\Models\RentOutUtilityTerm;

class StatusChangeAction
{
    public function execute($id, ServiceRequestStatus $status, $userId)
    {
        try {
            $model = PropertyServiceRequest::with('utility')->find($id);
            if (! $model) {
                throw new \Exception("Service Request not found with the specified ID: $id.", 1);
            }
            if ($model->status->next() !== $status) {
                throw new \Exception("A {$model->status->label()} Service Request cannot be moved to {$status->label()}.", 1);
            }

            $stamp = match ($status) {
                ServiceRequestStatus::Approved => 'approved',
                ServiceRequestStatus::FinancialApproved => 'financial_approved',
                ServiceRequestStatus::Completed => 'completed',
            };

            if ($status === ServiceRequestStatus::Approved) {
                $this->chargeUtility($model, $userId);
            }

            $model->update([
                'status' => $status,
                "{$stamp}_by" => $userId,
                "{$stamp}_at" => now(),
                'updated_by' => $userId,
            ]);

            $return['success'] = true;
            $return['message'] = "Service Request {$status->label()}";
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * Approving a connect on an occupied unit bills the tenant through a
     * utility term, unless the rent already includes that utility or the
     * charge for that date is already on the rent-out.
     */
    private function chargeUtility(PropertyServiceRequest $model, $userId): void
    {
        if ($model->type !== ServiceRequestType::Connect || (float) $model->amount <= 0) {
            return;
        }

        $rentOut = RentOut::where('property_id', $model->property_id)->occupied()->latest('start_date')->first();
        if (PropertyServiceRequest::rentOutServiceStatus($rentOut, $model->utility) !== 'Excluded') {
            return;
        }

        $duplicate = RentOutUtilityTerm::where('rent_out_id', $rentOut->id)
            ->where('utility_id', $model->utility_id)
            ->whereDate('date', $model->date)
            ->exists();
        if ($duplicate) {
            return;
        }

        $response = (new UtilityTermCreateAction())->execute([
            'branch_id' => $rentOut->branch_id,
            'rent_out_id' => $rentOut->id,
            'utility_id' => $model->utility_id,
            'amount' => $model->amount,
            'paid' => 0,
            'date' => $model->date->format('Y-m-d'),
            'remarks' => "Service Request #{$model->id}",
            'created_by' => $userId,
        ]);
        if (! $response['success']) {
            throw new \Exception($response['message'], 1);
        }
    }
}
