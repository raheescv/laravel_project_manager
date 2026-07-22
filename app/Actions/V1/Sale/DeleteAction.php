<?php

namespace App\Actions\V1\Sale;

use App\Actions\Sale\DeleteAction as WebDeleteAction;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    /**
     * Soft-delete a sale (with its items and payments) through the shared web
     * action, inside a transaction — mirroring the web Sale table delete.
     *
     * A business-rule failure (e.g. a completed sale, which must be returned or
     * cancelled instead to keep the accounting intact) is re-thrown as a
     * DomainException so the controller can surface the exact message and the
     * transaction rolls back untouched.
     */
    public function execute(int $saleId, ?int $userId): void
    {
        DB::transaction(function () use ($saleId, $userId): void {
            $result = (new WebDeleteAction())->execute($saleId, $userId);
            if (! ($result['success'] ?? false)) {
                throw new \DomainException($result['message'] ?? 'Failed to delete the sale.');
            }
        });
    }
}
