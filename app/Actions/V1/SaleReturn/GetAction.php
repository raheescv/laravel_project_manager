<?php

namespace App\Actions\V1\SaleReturn;

use App\Models\SaleReturn;

class GetAction
{
    /**
     * Retrieve a sale return with everything needed to render its receipt.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $saleReturnId): SaleReturn
    {
        return SaleReturn::query()
            ->with([
                'items.product:id,name,name_arabic,type',
                'items.employee:id,name',
                'payments.paymentMethod:id,name',
                'account:id,name,mobile',
                'createdUser:id,name',
                'branch',
            ])
            ->findOrFail($saleReturnId);
    }
}
