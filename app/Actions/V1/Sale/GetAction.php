<?php

namespace App\Actions\V1\Sale;

use App\Http\Resources\V1\Sale\SaleResource;
use App\Models\Sale;

class GetAction
{
    /**
     * Retrieve a completed bill with everything needed to print a receipt.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $saleId): Sale
    {
        return Sale::query()
            ->with(SaleResource::RELATIONS)
            ->findOrFail($saleId);
    }
}
