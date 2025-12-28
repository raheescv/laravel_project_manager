<?php

namespace App\Actions\Package\Payment;

use App\Models\Package;
use App\Models\PackagePayment;
use Exception;

class DeleteAction
{
    public function execute($id)
    {
        try {
            $model = PackagePayment::find($id);
            if (! $model) {
                throw new Exception("Package Payment not found with the specified ID: $id.", 1);
            }

            $packageId = $model->package_id;

            if (! $model->delete()) {
                throw new Exception('Oops! Something went wrong while deleting the Package Payment. Please try again.', 1);
            }

            // Update package paid amount
            $package = Package::find($packageId);
            if ($package) {
                $package->updatePaidAmount();
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Deleted Package Payment';
            $return['data'] = $package;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
