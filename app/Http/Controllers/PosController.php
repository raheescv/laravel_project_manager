<?php

namespace App\Services;

use App\Models\CustomerMeasurement;
use App\Models\MeasurementTemplate;

class MeasurementService
{
    // ...existing code...

    public function getCustomerMeasurements($customerId, $categoryId)
    {
        $templates = MeasurementTemplate::where('category_id', $categoryId)->get();
        $customerMeasurements = CustomerMeasurement::where('customer_id', $customerId)
            ->where('category_id', $categoryId)
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    $row->measurement_template_id => $row->value
                ];
            });
        // Attach template values to each measurement
        $result = [];
        foreach ($templates as $tpl) {
            $result[$tpl->id] = [
                'value' => $customerMeasurements[$tpl->id] ?? null,
                'template_values' => $tpl->values // comma-separated string or null
            ];
        }
        return $result;
    }

    // ...existing code...
}