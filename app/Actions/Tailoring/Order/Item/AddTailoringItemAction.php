<?php

namespace App\Actions\Tailoring\Order\Item;

use App\Models\TailoringCategoryMeasurement;
use App\Models\TailoringOrderItem;
use App\Models\TailoringOrderMeasurement;
use Exception;

class AddTailoringItemAction
{
    public function execute(array $data, int $user_id): array
    {
        try {
            $data['created_by'] = $data['updated_by'] = $user_id;

            // save measurement
            if (! empty($data['tailoring_category_id'])) {
                $categoryId = $data['tailoring_category_id'];
                // Fetch only keys that are defined for this category
                $activeKeys = TailoringCategoryMeasurement::where('tailoring_category_id', $categoryId)
                    ->where('is_active', true)
                    ->pluck('field_key')
                    ->toArray();

                $measurementData = [];
                $dynamicData = [];
                $measurementData['tailoring_category_model_id'] = $data['tailoring_category_model_id'];
                $measurementData['tailoring_category_model_type_id'] = $data['tailoring_category_model_type_id'] ?? null;

                if (isset($data['tailoring_notes'])) {
                    $measurementData['tailoring_notes'] = $data['tailoring_notes'];
                }
                foreach ($activeKeys as $key) {
                    if (array_key_exists($key, $data)) {
                        $value = $data[$key];
                        if (in_array($key, ['tailoring_category_model_id', 'tailoring_category_model_type_id', 'tailoring_notes', 'id', 'tailoring_order_id', 'tailoring_category_id'])) {
                            continue;
                        }
                        $dynamicData[(string) $key] = $value;
                    }
                }

                $measurementData['data'] = (object) $dynamicData;

                TailoringOrderMeasurement::updateOrCreate(
                    [
                        'tailoring_order_id' => $data['tailoring_order_id'],
                        'tailoring_category_id' => $categoryId,
                        'tailoring_category_model_id' => $data['tailoring_category_model_id'],
                        'tailoring_category_model_type_id' => $data['tailoring_category_model_type_id'] ?? null,
                    ],
                    $measurementData
                );
            }

            validationHelper(TailoringOrderItem::rules(), $data);

            $item = new TailoringOrderItem($data);
            $item->save();

            // Update order totals
            $order = $item->order;
            $order->calculateTotals();
            $order->save();

            $return['success'] = true;
            $return['message'] = 'Successfully Added Tailoring Order Item';
            $return['data'] = $item;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
