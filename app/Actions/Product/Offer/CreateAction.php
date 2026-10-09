<?php

namespace App\Actions\Product\Offer;

use App\Models\ProductOffer;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * @param  array{type: string, name: string, start_date: string, end_date: string, status: string, items: array<int, array{product_id: int, amount: float}>}  $data
     * @return array{success: bool, message: string, data?: ProductOffer}
     */
    public function execute(array $data, int $userId): array
    {
        try {
            $model = DB::transaction(function () use ($data, $userId): ProductOffer {
                validationHelper(ProductOffer::rules(), $data);

                $model = ProductOffer::create([
                    'type' => $data['type'],
                    'name' => $data['name'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'status' => $data['status'],
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                (new SyncPricesAction())->execute($model, $data['items']);

                return $model;
            });

            $return['success'] = true;
            $return['message'] = 'Offer saved.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
