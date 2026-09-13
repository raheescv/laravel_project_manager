<?php

namespace App\Actions\V1\Storefront;

use App\Actions\Account\CreateAction as AccountCreateAction;
use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Exceptions\StorefrontCheckoutException;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\Sale;
use App\Models\StorefrontCheckout;
use App\Models\User;
use App\Services\Payment\TapClient;
use App\Support\Storefront\TapSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncCheckoutAction
{
    /** Tap statuses that end a charge without taking the customer's money. */
    private const UNPAID_OUTCOMES = ['ABANDONED', 'CANCELLED', 'FAILED', 'DECLINED', 'RESTRICTED', 'VOID', 'TIMEDOUT'];

    /**
     * Bring a checkout in line with its Tap charge, recording the sale once paid.
     *
     * Runs when the customer lands back on the storefront AND when Tap's webhook
     * arrives — usually within the same second — and again whenever the storefront
     * re-checks. It is idempotent: a paid checkout is returned untouched, and the
     * row lock makes a concurrent second call find the first call's sale.
     *
     * The outcome is always read from Tap with the secret key, never taken from a
     * webhook body or the redirect's tap_id. That call is what makes it trustworthy.
     *
     * @throws \App\Services\Payment\TapException when Tap cannot be asked
     */
    public function execute(StorefrontCheckout $checkout): StorefrontCheckout
    {
        if ($checkout->status === StorefrontCheckout::STATUS_PAID || ! $checkout->gateway_charge_id) {
            return $checkout;
        }

        $settings = TapSettings::current();

        if (! $settings->secretKey) {
            throw new StorefrontCheckoutException('Online payment is not configured for this store.');
        }

        // Asked before the lock is taken so the row is never held across the HTTP call.
        $charge = (new TapClient($settings->secretKey))->retrieveCharge($checkout->gateway_charge_id);

        return DB::transaction(function () use ($checkout, $charge, $settings) {
            $locked = StorefrontCheckout::query()->whereKey($checkout->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === StorefrontCheckout::STATUS_PAID) {
                return $locked;
            }

            if (($charge['id'] ?? null) !== $locked->gateway_charge_id) {
                throw new StorefrontCheckoutException('The payment service returned a different charge.');
            }

            $status = strtoupper((string) ($charge['status'] ?? 'UNKNOWN'));
            $locked->fill(['gateway_status' => $status, 'gateway_response' => $charge]);

            if ($status === 'CAPTURED') {
                $this->settle($locked, $charge, $settings);
            } elseif (in_array($status, self::UNPAID_OUTCOMES, true)) {
                $locked->fill([
                    'status' => StorefrontCheckout::STATUS_FAILED,
                    'failure_reason' => $charge['response']['message'] ?? $status,
                ]);
            }
            // INITIATED / UNKNOWN: the customer has not finished paying — still pending.

            $locked->save();

            return $locked;
        });
    }

    /**
     * Money is in: record the completed sale, or park the checkout for review.
     *
     * A sale that cannot be recorded (the shop sold the last pair meanwhile and
     * out-of-stock selling is blocked, the payment account was deleted…) must not
     * erase the fact that the customer paid. Its writes roll back to a savepoint
     * and the checkout is kept as REVIEW with the reason instead; the next sync
     * tries again.
     */
    private function settle(StorefrontCheckout $checkout, array $charge, TapSettings $settings): void
    {
        $captured = round((float) ($charge['amount'] ?? 0), 2);
        $currency = strtoupper((string) ($charge['currency'] ?? ''));

        if (abs($captured - (float) $checkout->amount) > 0.001 || $currency !== $checkout->currency) {
            $this->needsReview($checkout, "Captured {$currency} {$captured} does not match the order total of {$checkout->currency} {$checkout->amount}.");

            return;
        }

        try {
            $sale = DB::transaction(fn () => $this->recordSale($checkout, $settings));
        } catch (\Throwable $e) {
            $this->needsReview($checkout, $e->getMessage());

            return;
        }

        $checkout->fill([
            'status' => StorefrontCheckout::STATUS_PAID,
            'sale_id' => $sale->id,
            'paid_at' => now(),
            'failure_reason' => null,
        ]);
    }

    /**
     * Create the completed sale through the web action, so stock and the journal
     * post exactly as they do for a sale rung up in the shop.
     */
    private function recordSale(StorefrontCheckout $checkout, TapSettings $settings): Sale
    {
        $user = User::query()->whereKey($settings->userId)->first();
        if (! $user) {
            throw new RuntimeException('The user set to record online sales no longer exists.');
        }

        $account = Account::query()->whereKey($settings->paymentAccountId)->first();
        if (! $account) {
            throw new RuntimeException('The payment account set for online sales no longer exists.');
        }

        $customer = $this->resolveCustomer($checkout->customer_name, $checkout->customer_mobile);
        $amount = (float) $checkout->amount;

        $data = [
            'status' => 'completed',
            'source' => 'storefront',
            'branch_id' => $checkout->branch_id,
            // The Tap charge id, so the sale can be matched to the payment in Tap's dashboard.
            'reference_no' => $checkout->gateway_charge_id,
            'account_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_mobile' => $customer->mobile,
            'sale_type' => 'normal',
            'date' => today()->toDateString(),
            'gross_amount' => 0,
            'item_discount' => 0,
            'tax_amount' => 0,
            'other_discount' => 0,
            'freight' => 0,
            'tip' => 0,
            'round_off' => 0,
            'address' => $checkout->address,
            'payment_method_ids' => (string) $account->id,
            'payment_method_name' => $account->name,
            'paid' => $amount,
            'items' => array_map(fn (array $line) => [
                'employee_id' => $user->id,
                'inventory_id' => $line['inventory_id'],
                'product_id' => $line['product_id'],
                'unit_id' => $line['unit_id'],
                'unit_price' => $line['unit_price'],
                'quantity' => $line['quantity'],
                'conversion_factor' => 1,
                'discount' => 0,
                'tax' => $line['tax'],
            ], $checkout->items),
            'payments' => [['payment_method_id' => $account->id, 'amount' => $amount]],
            'comboOffers' => [],
        ];

        $response = (new SaleCreateAction())->execute($data, (int) $user->id);

        if (! $response['success']) {
            throw new RuntimeException($response['message']);
        }

        $sale = $response['data'];

        // The customer paid exactly the checkout amount; a sale that totals anything
        // else would show a balance due that was never owed.
        if (abs((float) $sale->grand_total - $amount) > 0.001) {
            throw new RuntimeException("The sale totals {$sale->grand_total} but {$amount} was paid.");
        }

        return $sale;
    }

    private function needsReview(StorefrontCheckout $checkout, string $reason): void
    {
        Log::error('Storefront checkout paid but no sale recorded', [
            'checkout' => $checkout->reference,
            'charge' => $checkout->gateway_charge_id,
            'reason' => $reason,
        ]);

        $checkout->fill(['status' => StorefrontCheckout::STATUS_REVIEW, 'failure_reason' => $reason]);
    }

    /**
     * The customer account for the sale — matched by name and mobile, the same
     * rule the mobile POS uses, so an online shopper and a walk-in with the same
     * details land on one account.
     */
    private function resolveCustomer(string $name, string $mobile): Account
    {
        $name = trim($name);
        $mobile = trim($mobile);

        $existing = Account::customer()->where('name', $name)->where('mobile', $mobile)->first();

        if ($existing) {
            return $existing;
        }

        $response = (new AccountCreateAction())->execute([
            'account_type' => 'asset',
            'account_category_id' => AccountCategory::firstOrCreate(['name' => 'Account Receivable'])->id,
            'name' => $name,
            'mobile' => $mobile,
            'model' => 'customer',
        ]);

        if (! $response['success']) {
            throw new RuntimeException($response['message']);
        }

        return $response['data'];
    }
}
