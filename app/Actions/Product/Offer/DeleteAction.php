<?php

namespace App\Actions\Product\Offer;

use App\Models\ProductOffer;
use Exception;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    /**
     * Delete the offer; its product_prices rows go with it (FK cascade), so
     * the products return to their normal price at once.
     *
     * @return array{success: bool, message: string, data?: ProductOffer}
     */
    public function execute(int $id): array
    {
        try {
            $model = DB::transaction(function () use ($id): ProductOffer {
                $model = ProductOffer::find($id);
                if (! $model) {
                    throw new Exception('Offer not found.', 1);
                }
                $model->prices()->delete();
                $model->delete();

                return $model;
            });

            $return['success'] = true;
            $return['message'] = 'Offer deleted.';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
