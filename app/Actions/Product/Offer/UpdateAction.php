<?php

namespace App\Actions\Product\Offer;

use App\Models\ProductOffer;
use Exception;
use Illuminate\Support\Facades\DB;

class UpdateAction
{
    /**
     * @param  array{name: string, start_date: string, end_date: string, status: string, items: array<int, array{product_id: int, amount: float}>}  $data
     * @return array{success: bool, message: string, data?: ProductOffer}
     */
    public function execute(array $data, int $id, int $userId): array
    {
        try {
            $model = DB::transaction(function () use ($data, $id, $userId): ProductOffer {
                $model = ProductOffer::find($id);
                if (! $model) {
                    throw new Exception('Offer not found.', 1);
                }

                $data['type'] = $model->type;
                validationHelper(ProductOffer::rules($id), $data);

                $model->update([
                    'name' => $data['name'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'status' => $data['status'],
                    'updated_by' => $userId,
                ]);

                (new SyncPricesAction())->execute($model, $data['items']);

                return $model;
            });

            $return['success'] = true;
            $return['message'] = 'Offer updated.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
