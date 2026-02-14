<?php

namespace App\Actions\Tailoring\Order\Item;

use App\Models\TailoringCategoryMeasurement;
use App\Models\TailoringOrderItem;
use App\Models\TailoringOrderMeasurement;
use Exception;

class UpdateTailoringItemAction
{
    public function execute($id, array $data, int $user_id): array
    {
        try {
            $item = TailoringOrderItem::findOrFail($id);
            $data['updated_by'] = $user_id;

            // save measurement
            $categoryId = $data['tailoring_category_id'] ?? $item->tailoring_category_id;
            if ($categoryId) {
                // Fetch only keys that are defined for this category
                $activeKeys = TailoringCategoryMeasurement::where('tailoring_category_id', $categoryId)
                    ->where('is_active', true)
                    ->pluck('field_key')
                    ->toArray();
                $measurementData = [
                    'tailoring_order_id' => $item->tailoring_order_id,
                    'tailoring_category_id' => $categoryId,
                ];
                $dynamicData = [];

                $measurementData['tailoring_category_model_id'] = $data['tailoring_category_model_id'];

                if (isset($data['tailoring_notes'])) {
                    $measurementData['tailoring_notes'] = $data['tailoring_notes'];
                }
                foreach ($activeKeys as $key) {
                    if (array_key_exists($key, $data)) {
                        $value = $data[$key];
                        if ($key === 'tailoring_category_model_id' || $key === 'tailoring_notes' || $key === 'id' || $key === 'tailoring_order_id' || $key === 'tailoring_category_id') {
                            continue;
                        }
                        $dynamicData[(string) $key] = $value;
                    }
                }
                $measurementData['data'] = (object) $dynamicData;
                TailoringOrderMeasurement::updateOrCreate(
                    [
                        'tailoring_order_id' => $item->tailoring_order_id,
                        'tailoring_category_id' => $categoryId,
                        'tailoring_category_model_id' => $data['tailoring_category_model_id'],
                    ],
                    $measurementData
                );
            }

            validationHelper(TailoringOrderItem::rules($id), $data);
            $item->fill($data);
            $item->save();

            // Update order totals
            $order = $item->order;
            $order->calculateTotals();
            $order->save();

            $return['success'] = true;
            $return['message'] = 'Successfully Updated Tailoring Order Item';
            $return['data'] = $item;
        } catch (Exception $e) {
            $return['success'] = false;
            $return['message'] = $e->getMessage();
        }

        return $return;
    }
}
