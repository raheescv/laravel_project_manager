<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Mobile - Sales')]
class PaymentMethodController extends Controller
{
    use ApiResponseTrait;

    /**
     * List payment methods.
     *
     * Returns the payment-method accounts configured for the business (the same
     * set the web POS offers under "Custom Payment"), so the app can present them
     * in the custom-payment selector.
     */
    public function index(): JsonResponse
    {
        try {
            $methods = Account::query()
                ->whereIn('id', cache('payment_methods', []))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Account $account) => [
                    'id' => $account->id,
                    'name' => $account->name,
                ])
                ->values();

            return $this->sendSuccess($methods, 'Payment methods retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve payment methods: '.$e->getMessage());
        }
    }
}
