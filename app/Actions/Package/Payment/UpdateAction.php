<?php

namespace App\Actions\Package\Payment;

use App\Models\Package;
use App\Models\PackagePayment;
use Exception;
use Illuminate\Support\Facades\Auth;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = PackagePayment::find($id);
            if (! $model) {
                throw new Exception("Package Payment not found with the specified ID: $id.", 1);
            }
            $data['updated_by'] = Auth::id();
            validationHelper(PackagePayment::rules($id), $data);

            $model->update($data);

            // Update package paid amount
            $package = Package::find($model->package_id);
            if ($package) {
                $package->updatePaidAmount();
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Package Payment';
            $return['data'] = $model;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
