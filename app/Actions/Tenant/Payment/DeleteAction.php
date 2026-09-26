<?php

namespace App\Actions\Tenant\Payment;

use App\Models\TenantPayment;

class DeleteAction
{
    /**
     * Remove a payment. When it was the one that set the tenant's current
     * renewal date, that date goes back to what it was before.
     *
     * @return array{success: bool, message: string}
     */
    public function execute(int $id, int $tenantId): array
    {
        try {
            $payment = TenantPayment::where('tenant_id', $tenantId)->findOrFail($id);
            $tenant = $payment->tenant;

            if ($payment->renewed_to && $tenant->renews_on?->isSameDay($payment->renewed_to)) {
                $tenant->update(['renews_on' => $payment->renewed_from]);
            }
            $payment->delete();

            $return['success'] = true;
            $return['message'] = 'Payment deleted';
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
