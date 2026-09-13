<?php

namespace App\Actions\V1\Storefront;

use App\Exceptions\StorefrontCheckoutException;
use App\Http\Requests\V1\Storefront\StartCheckoutRequest;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StorefrontCheckout;
use App\Services\Payment\TapClient;
use App\Services\Payment\TapException;
use App\Services\TenantService;
use App\Support\Storefront\TapSettings;
use Illuminate\Support\Str;

class StartCheckoutAction
{
    /**
     * Price the bag, hold it as a pending checkout and open a Tap charge for it.
     *
     * Nothing here touches stock or the books — that happens only once Tap reports
     * the charge captured (SyncCheckoutAction). A double-tapped Pay therefore
     * costs nothing but a second, never-paid charge.
     *
     * No transaction around the whole thing: the checkout row must survive a
     * failed call to Tap so the attempt stays on record, and a transaction held
     * open across an HTTP round trip would protect nothing.
     */
    public function execute(StartCheckoutRequest $request): StorefrontCheckout
    {
        $settings = TapSettings::current();

        if (! $settings->isReady()) {
            throw new StorefrontCheckoutException('Online payment is not available for this store yet.');
        }

        $fulfilment = $request->validated('fulfilment');
        $currency = $this->currency();
        $branch = $this->resolveBranch($fulfilment, $request->validated('branchId'), $settings);
        $items = $this->priceItems($request->validated('items'), $branch);
        $amount = round(array_sum(array_column($items, 'total')), 2);

        if ($amount <= 0) {
            throw new StorefrontCheckoutException('There is nothing in your bag to pay for.');
        }

        $checkout = StorefrontCheckout::create([
            'reference' => Str::random(32),
            'branch_id' => $branch->id,
            'fulfilment' => $fulfilment,
            'customer_name' => trim($request->validated('customerName')),
            'customer_mobile' => $request->validated('customerMobile'),
            'customer_email' => $request->validated('customerEmail'),
            'address' => $fulfilment === 'delivery' ? trim((string) $request->validated('address')) : null,
            'items' => $items,
            'amount' => $amount,
            'currency' => $currency,
            'gateway' => 'tap',
            'status' => StorefrontCheckout::STATUS_PENDING,
        ]);

        try {
            $charge = (new TapClient($settings->secretKey))->createCharge($this->chargePayload(
                $checkout,
                $request->validated('countryCode'),
                $request->validated('returnUrl'),
                $settings,
            ));
        } catch (TapException $e) {
            $checkout->update(['status' => StorefrontCheckout::STATUS_FAILED, 'failure_reason' => $e->getMessage()]);

            throw new StorefrontCheckoutException('We could not start the payment. Please try again in a moment.');
        }

        $checkout->update([
            'gateway_charge_id' => $charge['id'] ?? null,
            'gateway_status' => $charge['status'] ?? null,
            'gateway_response' => $charge,
        ]);

        if (empty($charge['id']) || empty($charge['transaction']['url'])) {
            $checkout->update(['status' => StorefrontCheckout::STATUS_FAILED, 'failure_reason' => 'Tap returned no payment page for the charge.']);

            throw new StorefrontCheckoutException('We could not start the payment. Please try again in a moment.');
        }

        return $checkout;
    }

    /**
     * Where the order's stock comes from.
     *
     * Pickup: the shop the customer chose — only shops the storefront lists, since a
     * back-office branch hidden from the showcase is not somewhere to collect from.
     * Delivery: the branch set in Settings → Online Payments; none means no delivery.
     */
    private function resolveBranch(string $fulfilment, mixed $branchId, TapSettings $settings): Branch
    {
        if ($fulfilment === 'delivery') {
            $branch = $settings->deliveryEnabled() ? Branch::query()->whereKey($settings->deliveryBranchId)->first() : null;

            if (! $branch) {
                throw new StorefrontCheckoutException('Delivery is not available — please choose a shop to collect from.');
            }

            return $branch;
        }

        $branch = Branch::query()->whereKey($branchId)->where('exclude_from_showcase', false)->first();

        if (! $branch) {
            throw new StorefrontCheckoutException('Please choose a shop to collect your order from.');
        }

        return $branch;
    }

    /**
     * Price every line from the catalogue and check [branch] can fill it.
     *
     * The amounts mirror the sale_items generated columns (net = price × qty, tax
     * on net, each at 2 dp) so the charge equals the grand total the sale carries.
     *
     * @param  array<int, array{productId: int, quantity: int}>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function priceItems(array $lines, Branch $branch): array
    {
        // Two bag lines for the same product become one sale line: the sale
        // refuses a repeated product under the same employee.
        $quantities = [];
        foreach ($lines as $line) {
            $productId = (int) $line['productId'];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $line['quantity'];
        }

        $products = Product::query()
            ->whereIn('id', array_keys($quantities))
            ->where('type', 'product')
            ->get()
            ->keyBy('id');
        $inventories = Inventory::query()
            ->whereIn('product_id', array_keys($quantities))
            ->where('branch_id', $branch->id)
            ->get()
            ->keyBy('product_id');

        $items = [];

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product) {
                throw new StorefrontCheckoutException('An item in your bag is no longer available. Please remove it and try again.');
            }

            $label = $product->name.($product->size ? " (size {$product->size})" : '');
            $inventory = $inventories->get($productId);
            $available = max(0, (int) floor((float) ($inventory?->quantity ?? 0)));

            if (! $inventory || $available < $quantity) {
                throw new StorefrontCheckoutException($available > 0
                    ? "Only {$available} of {$label} left at {$branch->name}."
                    : "{$label} is sold out at {$branch->name}.");
            }

            $unitPrice = round((float) $product->mrp, 2);
            $taxRate = (float) ($product->tax ?? 0);
            $net = round($unitPrice * $quantity, 2);
            $tax = round($net * $taxRate / 100, 2);

            $items[] = [
                'product_id' => $product->id,
                'inventory_id' => $inventory->id,
                'unit_id' => $product->unit_id ?: 1,
                'name' => $product->name,
                'size' => $product->size,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax' => $taxRate,
                'total' => round($net + $tax, 2),
            ];
        }

        return $items;
    }

    /**
     * ISO code of the store's base currency — the currency catalogue prices are in.
     */
    private function currency(): string
    {
        $code = strtoupper(trim((string) (tenant_cache('base_currency_code') ?: tenant_cache('currency_code'))));

        if (! preg_match('/^[A-Z]{3}$/', $code)) {
            throw new StorefrontCheckoutException('Online payment is not available: the store currency is not set.');
        }

        return $code;
    }

    /**
     * The Tap create-charge body.
     *
     * @see https://developers.tap.company/reference/create-a-charge
     */
    private function chargePayload(StorefrontCheckout $checkout, string $countryCode, string $returnUrl, TapSettings $settings): array
    {
        [$firstName, $lastName] = $this->splitName($checkout->customer_name);
        $subdomain = app(TenantService::class)->getCurrentTenant()?->subdomain;

        $payload = [
            'amount' => (float) $checkout->amount,
            'currency' => $checkout->currency,
            'customer_initiated' => true,
            'threeDSecure' => true,
            'save_card' => false,
            'description' => Str::limit(trim((tenant_cache('company_name') ?: 'Online').' order '.$checkout->reference), 250, ''),
            'metadata' => ['checkout' => $checkout->reference],
            'reference' => ['transaction' => $checkout->reference, 'order' => $checkout->reference],
            'customer' => array_filter([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $checkout->customer_email,
                'phone' => ['country_code' => (int) $countryCode, 'number' => (int) $checkout->customer_mobile],
            ]),
            // Every method switched on for the merchant, on Tap's hosted page.
            'source' => ['id' => 'src_all'],
            // The tenant hint keeps the webhook resolvable when the API is reached
            // on a bare host (IP / localhost) where no subdomain can be parsed.
            'post' => ['url' => route('api.v1.storefront.checkout.webhook', array_filter(['tenant' => $subdomain]))],
            'redirect' => ['url' => $this->redirectUrl($returnUrl, $checkout->reference)],
        ];

        if ($settings->merchantId) {
            $payload['merchant'] = ['id' => $settings->merchantId];
        }

        return $payload;
    }

    /**
     * Tap appends `?tap_id=…` to this URL. The storefront routes on the #hash, and
     * a query glued on after a fragment would be swallowed by it, so the fragment
     * is dropped and the checkout reference travels in the real query string.
     */
    private function redirectUrl(string $returnUrl, string $reference): string
    {
        $base = Str::before($returnUrl, '#');

        return $base.(str_contains($base, '?') ? '&' : '?').'checkout='.$reference;
    }

    /** @return array{0: string, 1: string|null} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [$parts[0], $parts[1] ?? null];
    }
}
