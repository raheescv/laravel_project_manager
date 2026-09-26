<?php

namespace App\Actions\Tenant\Payment;

use App\Models\Tenant;
use App\Models\TenantPayment;

class CreateAction
{
    /**
     * Record a payment. An AMC payment with $extendRenewal moves the tenant's
     * renewal date one AMC cycle forward (from today when it has none) and
     * keeps the old date on the payment so DeleteAction can undo it.
     *
     * @param  array{tenant_id: int, paid_on: string, type: string, amount: float|string, method?: ?string, reference?: ?string, note?: ?string}  $data
     * @return array{success: bool, message: string, data?: TenantPayment}
     */
    public function execute(array $data, bool $extendRenewal = false): array
    {
        try {
            validationHelper(TenantPayment::rules(), $data);

            $tenant = Tenant::withTrashed()->findOrFail($data['tenant_id']);
            $data['created_by'] = auth()->id();

            if ($extendRenewal && $data['type'] === 'amc') {
                $from = $tenant->renews_on ?? today();
                $data['renewed_from'] = $tenant->renews_on?->toDateString();
                $data['renewed_to'] = $tenant->nextRenewalFrom($from)->toDateString();
                $tenant->update(['renews_on' => $data['renewed_to']]);
            }

            $model = TenantPayment::create($data);

            $return['success'] = true;
            $return['message'] = isset($data['renewed_to'])
                ? 'Payment recorded · renews on '.$tenant->renews_on->format('d M Y')
                : 'Payment recorded';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
