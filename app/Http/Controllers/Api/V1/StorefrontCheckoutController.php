<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Storefront\StartCheckoutAction;
use App\Actions\V1\Storefront\SyncCheckoutAction;
use App\Exceptions\StorefrontCheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Storefront\StartCheckoutRequest;
use App\Http\Resources\V1\Storefront\CheckoutResource;
use App\Models\StorefrontCheckout;
use App\Services\Payment\TapException;
use App\Support\Storefront\TapSettings;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Public - Storefront')]
class StorefrontCheckoutController extends Controller
{
    use ApiResponseTrait;

    /**
     * Checkout availability.
     *
     * Whether the storefront can take payment (Settings → Online Payments is
     * switched on and complete), whether delivery is offered, and whether the saved
     * Tap key is a test key — the storefront labels test checkouts as such.
     */
    public function config(): JsonResponse
    {
        $settings = TapSettings::current();
        $ready = $settings->isReady();

        return $this->sendSuccess([
            'enabled' => $ready,
            'delivery' => $ready && $settings->deliveryEnabled(),
            'test_mode' => $ready && ! $settings->isLiveMode(),
        ], 'Checkout configuration retrieved successfully');
    }

    /**
     * Start a checkout.
     *
     * Prices the bag from the catalogue (the browser only sends product ids and
     * quantities), checks the chosen shop has the stock, and opens a Tap charge.
     * Send the customer to `payment_url`; Tap brings them back to `returnUrl` with
     * `?checkout={reference}&tap_id=…` appended.
     */
    public function store(StartCheckoutRequest $request, StartCheckoutAction $action): JsonResponse
    {
        try {
            $checkout = $action->execute($request);

            return $this->sendSuccess(new CheckoutResource($checkout->load('branch:id,name')), 'Checkout started', 201);
        } catch (StorefrontCheckoutException $e) {
            return $this->sendError($e->getMessage(), [], 422);
        } catch (\Throwable $e) {
            report($e);

            return $this->sendServerError('Checkout could not be started. Please try again.');
        }
    }

    /**
     * Checkout status.
     *
     * Re-checks the charge with Tap — recording the completed sale once it is
     * captured — and reports where the checkout stands. The storefront calls this
     * when the customer lands back from Tap. If Tap cannot be reached, the last
     * known state is returned unchanged.
     */
    public function show(string $reference, SyncCheckoutAction $action): JsonResponse
    {
        $checkout = StorefrontCheckout::query()->where('reference', $reference)->first();

        if (! $checkout) {
            return $this->sendNotFoundError('Checkout not found');
        }

        try {
            $checkout = $action->execute($checkout);
        } catch (TapException|StorefrontCheckoutException) {
            // Report what is already known; the storefront offers "check again".
        } catch (\Throwable $e) {
            report($e);

            return $this->sendServerError('Checkout status could not be checked. Please try again.');
        }

        return $this->sendSuccess(
            new CheckoutResource($checkout->load(['branch:id,name', 'sale:id,invoice_no'])),
            'Checkout retrieved successfully',
        );
    }

    /**
     * Tap webhook.
     *
     * Tap POSTs the finished charge here (its `post.url`), which covers a customer
     * who pays and closes the tab before being redirected back. The body is only
     * used to find the checkout: the outcome is re-read from Tap with the secret
     * key before anything is recorded, so a forged post can at most trigger a
     * status check. A failure answers 500 so Tap retries.
     */
    public function webhook(Request $request, SyncCheckoutAction $action): JsonResponse
    {
        $chargeId = $request->input('id');

        $checkout = is_string($chargeId) && $chargeId !== ''
            ? StorefrontCheckout::query()->where('gateway_charge_id', $chargeId)->first()
            : null;

        if (! $checkout) {
            // Not a charge this tenant started: acknowledge so Tap stops retrying.
            return $this->sendSuccess(null, 'Ignored');
        }

        try {
            $action->execute($checkout);
        } catch (\Throwable $e) {
            report($e);

            return $this->sendServerError('Webhook could not be processed');
        }

        return $this->sendSuccess(null, 'Processed');
    }
}
