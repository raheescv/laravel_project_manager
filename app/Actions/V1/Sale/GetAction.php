<?php

namespace App\Actions\V1\Sale;

use App\Models\Sale;

class GetAction
{
    /**
     * Retrieve a completed bill with everything needed to print a receipt.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function execute(int $billId): Sale
    {
        return Sale::query()
            ->with([
                'items.product:id,name,type',
                'items.employee:id,name',
                'payments.paymentMethod:id,name',
                'account:id,name,mobile',
                'createdUser:id,name',
                'branch',
            ])
            ->findOrFail($billId);
    }
}
