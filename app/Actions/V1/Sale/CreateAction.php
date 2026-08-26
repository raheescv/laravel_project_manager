<?php

namespace App\Actions\V1\Sale;

use App\Actions\Account\CreateAction as AccountCreateAction;
use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Http\Requests\V1\Sale\StoreRequest;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\ApiLog;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateAction
{
    /**
     * How recent an identical sale must be to be treated as an accidental duplicate.
     */
    private const DUPLICATE_WINDOW_MINUTES = 2;

    /**
     * Create a completed sale from the final sale data sent by the mobile app.
     *
     * Persistence is delegated to the existing App\Actions\Sale\CreateAction so that
     * stock movements and journal postings run exactly as they do for the web POS.
     */
    public function execute(StoreRequest $request): Sale
    {
        $apiLog = $this->startApiLog($request);

        try {
            $poster = $request->user();

            // A sale queued offline carries the cashier who took it, because a
            // shared till is very often signed in as somebody else by the time
            // the queue drains. Without this the takings would be filed under
            // the wrong person — and refusing to drain them instead would strand
            // real money on the device until that cashier came back.
            //
            // The branch always follows that cashier's own assignment; it is
            // never taken from the request, so this cannot be used to post a
            // sale into someone else's branch.
            $user = $this->resolveCashier($poster, $request->validated('clientUserId'));
            $branchId = $user->default_branch_id;

            if (! $branchId) {
                throw new RuntimeException('Your account is not assigned to a branch.');
            }

            $clientUuid = $request->validated('clientUuid');

            // A sale queued offline is replayed until the device sees a success,
            // so the same key can arrive more than once. Hand back the committed
            // sale rather than ringing it up twice.
            if ($clientUuid && $existing = $this->findByClientUuid($clientUuid)) {
                // Already recorded and then voided. Re-creating it would undo a
                // deliberate deletion, and the unique index would refuse anyway,
                // so this has to reach a person.
                if ($existing->trashed()) {
                    throw new RuntimeException('This sale was already recorded on this device and has since been voided. It cannot be synced again.');
                }

                $this->completeApiLog($apiLog, 'success', [
                    'sale_id' => $existing->id,
                    'invoice_no' => $existing->invoice_no,
                    'replayed' => true,
                ]);

                return $existing;
            }

            $customer = $this->resolveCustomer($request->validated('customerName'), $request->validated('phoneNumber'));
            $items = $this->buildItems($request->validated('items'), $branchId, (int) $user->id);
            $totalPayment = (float) $request->validated('totalPayment');
            $payment = $this->resolvePayments(
                $request->validated('paymentMethod'),
                $request->validated('payments') ?? [],
                $totalPayment,
            );

            // The heuristic guard only exists because an online sale has no
            // idempotency key. When one is supplied it is not merely redundant
            // but wrong: syncing a backlog posts many sales within seconds, and
            // two customers who each bought the same single item would look
            // identical to it — one of them would be refused.
            if (! $clientUuid) {
                $this->guardAgainstDuplicate($branchId, (int) $customer->id, (int) $user->id, $items, $payment['paid']);
            }

            $data = [
                'status' => $request->validated('status') ?: 'completed',
                'source' => 'api',
                'branch_id' => $branchId,
                'client_uuid' => $clientUuid,
                'client_created_at' => $request->validated('clientCreatedAt'),
                // The provisional reference the device printed on the customer's
                // receipt, kept in the sale's own reference field. Only ever
                // present on a sale that was queued offline, which is exactly when
                // such a receipt was printed — an online sale leaves this null and
                // the field stays free for whatever the back office types in it.
                'reference_no' => $request->validated('offlineRef'),
                'account_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_mobile' => $customer->mobile,
                'sale_type' => 'normal',
                'date' => today()->toDateString(),
                'gross_amount' => 0,
                'item_discount' => 0,
                'tax_amount' => 0,
                'other_discount' => (float) ($request->validated('discount') ?? 0),
                'freight' => 0,
                'tip' => (float) ($request->validated('tip') ?? 0),
                'round_off' => 0,
                'payment_method_ids' => $payment['ids'],
                'payment_method_name' => $payment['names'],
                'paid' => $payment['paid'],
                'items' => $items,
                'payments' => $payment['payments'],
                'comboOffers' => [],
            ];

            $sale = DB::transaction(function () use ($data, $user) {
                $response = (new SaleCreateAction())->execute($data, (int) $user->id);

                if (! $response['success']) {
                    throw new RuntimeException($response['message']);
                }

                return $response['data'];
            })->load([
                'items.product:id,name,type',
                'items.employee:id,name',
                'payments.paymentMethod:id,name',
                'account:id,name,mobile',
                'createdUser:id,name',
                'branch',
            ]);

            if ($request->boolean('sendToWhatsapp')) {
                $this->dispatchWhatsapp((int) $sale->id);
            }

            $this->completeApiLog($apiLog, 'success', [
                'sale_id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
            ]);

            return $sale;
        } catch (\Throwable $e) {
            // Two replays of the same queued sale can race: one commits, the
            // other loses the unique index. Resolving the winner here rather
            // than in a typed catch is deliberate — App\Actions\Sale\CreateAction
            // catches \Throwable and returns ['success' => false], so the PDO
            // UniqueConstraintViolationException never escapes it and arrives as
            // a plain RuntimeException carrying its message. Type-matching on it
            // would be dead code; asking the database is not.
            $uuid = $request->validated('clientUuid');
            $existing = $uuid ? $this->findByClientUuid($uuid) : null;

            if ($existing && ! $existing->trashed()) {
                $this->completeApiLog($apiLog, 'success', [
                    'sale_id' => $existing->id,
                    'invoice_no' => $existing->invoice_no,
                    'replayed' => true,
                ]);

                return $existing;
            }

            $this->completeApiLog($apiLog, 'failed', null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Whose sale this is.
     *
     * Defaults to the authenticated poster. A [$claimedUserId] is honoured only
     * when it names an ACTIVE user on this tenant — the tenant scope on the
     * lookup is what stops one business claiming another's staff. Anything else
     * silently falls back to the poster: an unrecognised claim must not cost the
     * sale, and mis-attributing it to a stranger would be worse than recording
     * the person who synced it.
     */
    private function resolveCashier(User $poster, ?int $claimedUserId): User
    {
        if (! $claimedUserId || (int) $claimedUserId === (int) $poster->id) {
            return $poster;
        }

        $claimed = User::query()->where('id', $claimedUserId)->where('is_active', 1)->first();

        return $claimed ?: $poster;
    }

    /**
     * Resolve a previously committed sale by its device-generated idempotency
     * key, loaded exactly like a freshly created one so the Resource renders the
     * same shape either way.
     */
    private function findByClientUuid(string $clientUuid): ?Sale
    {
        // The tenant scope stays on; only the assigned-branch scope is dropped.
        // If a branch reassignment hid the earlier sale, the lookup would miss
        // and we would ring it up a second time — the exact duplicate this key
        // exists to prevent.
        // `withTrashed` so the lookup sees exactly what the unique index sees:
        // the index counts soft-deleted rows, so a replay of a voided sale would
        // otherwise miss here, insert, and fail on the constraint with nothing
        // to explain it.
        return Sale::query()
            ->withoutGlobalScope(AssignedBranchScope::class)
            ->withTrashed()
            ->where('client_uuid', $clientUuid)
            ->with([
                'items.product:id,name,type',
                'items.employee:id,name',
                'payments.paymentMethod:id,name',
                'account:id,name,mobile',
                'createdUser:id,name',
                'branch',
            ])
            ->first();
    }

    /**
     * Persist an api_logs row at request entry so every Sale API call is auditable.
     */
    private function startApiLog(StoreRequest $request): ?ApiLog
    {
        try {
            return ApiLog::create([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'service_name' => 'Sale Create',
                'request' => json_encode($request->all()),
                'status' => 'pending',
                'user_id' => $request->user()?->id,
                'user_name' => $request->user()?->name,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Finalize the api_logs row with the outcome of the request.
     *
     * @param  array<string, mixed>|null  $response
     */
    private function completeApiLog(?ApiLog $apiLog, string $status, ?array $response = null, ?string $description = null): void
    {
        if (! $apiLog) {
            return;
        }

        try {
            $data = [
                'status' => $status,
                'response' => $response ? json_encode($response) : null,
                'description' => $description,
            ];
            $apiLog->update($data);
        } catch (\Throwable $e) {
            // Logging must never mask the real outcome of the request.
        }
    }

    /**
     * Map the request line items to the shape expected by Sale\CreateAction,
     * resolving each product's inventory record at the user's branch.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(array $lines, int $branchId, int $userId): array
    {
        $productIds = array_unique(array_map(fn ($line) => (int) $line['productId'], $lines));

        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');
        $inventories = Inventory::query()
            ->whereIn('product_id', $productIds)
            ->where('branch_id', $branchId)
            ->get()
            ->keyBy('product_id');

        $items = [];

        foreach ($lines as $line) {
            $productId = (int) $line['productId'];
            $product = $products->get($productId);

            if (! $product) {
                throw new RuntimeException("Product #{$productId} is not a valid sellable product.");
            }

            $inventory = $inventories->get($productId);

            if (! $inventory) {
                throw new RuntimeException("Product '{$product->name}' is not available at your branch.");
            }

            $items[] = [
                'employee_id' => isset($line['employeeId']) ? (int) $line['employeeId'] : $userId,
                'inventory_id' => $inventory->id,
                'product_id' => $product->id,
                'unit_id' => $product->unit_id ?: 1,
                'unit_price' => isset($line['unitPrice']) ? (float) $line['unitPrice'] : $product->mrp,
                'quantity' => (float) $line['quantity'],
                'conversion_factor' => 1,
                'discount' => (float) ($line['discount'] ?? 0),
                'tax' => $product->tax ?? 0,
            ];
        }

        return $items;
    }

    /**
     * Reject a sale that is byte-for-byte identical to one this user has just
     * created, guarding against double taps and network retries from the app.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function guardAgainstDuplicate(int $branchId, int $customerId, int $userId, array $items, float $totalPayment): void
    {
        $signature = $this->itemsSignature($items);

        $recentSales = Sale::query()
            ->where('branch_id', $branchId)
            ->where('account_id', $customerId)
            ->where('created_by', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMinutes(self::DUPLICATE_WINDOW_MINUTES))
            ->with('items:id,sale_id,product_id,quantity,unit_price,discount')
            ->latest('id')
            ->get();

        foreach ($recentSales as $sale) {
            if (abs((float) $sale->paid - $totalPayment) > 0.001) {
                continue;
            }

            $existing = $sale->items
                ->map(
                    fn ($item) => [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'discount' => $item->discount ?? 0,
                    ],
                )
                ->all();

            if ($this->itemsSignature($existing) === $signature) {
                throw new RuntimeException("This sale was already saved a moment ago (matches sale #{$sale->id}). Please refresh before trying again.");
            }
        }
    }

    /**
     * Build an order-independent fingerprint of the sale line items.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function itemsSignature(array $items): string
    {
        $rows = array_map(
            fn ($item) => [
                'product_id' => (int) $item['product_id'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'discount' => (float) ($item['discount'] ?? 0),
            ],
            $items,
        );

        usort($rows, fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

        return md5((string) json_encode($rows));
    }

    private function resolveCustomer(string $name, ?string $mobile): Account
    {
        $name = trim($name);
        $mobile = $mobile ? trim($mobile) : null;

        $existing = Account::customer();
        $existing = $existing->where('name', $name);
        if ($mobile) {
            $existing = $existing->where('mobile', $mobile);
        }
        $existing = $existing->first();

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

    /**
     * Resolve the payment breakdown for the sale, mirroring the web POS contract:
     *   - "credit" → no payment is recorded (paid = 0).
     *   - "custom" → the caller supplies one or more {payment_method_id, amount} rows.
     *   - any other value → treated as a method NAME, paid in full to that one account.
     *
     * @param  array<int, array<string, mixed>>  $customPayments
     * @return array{payments: array<int, array{payment_method_id: int, amount: float}>, paid: float, ids: string, names: string}
     */
    private function resolvePayments(string $method, array $customPayments, float $totalPayment): array
    {
        $method = trim($method);

        if (strcasecmp($method, 'credit') === 0) {
            return ['payments' => [], 'paid' => 0.0, 'ids' => '', 'names' => 'Credit'];
        }

        $configured = $this->configuredPaymentMethods();

        if ($configured->isEmpty()) {
            throw new RuntimeException('No payment methods are configured for this business.');
        }

        if (strcasecmp($method, 'custom') === 0) {
            return $this->buildCustomPayments($customPayments, $configured);
        }

        $account = $configured->first(fn (Account $a) => stripos($a->name, $method) !== false);

        if (! $account) {
            throw new RuntimeException("Payment method '{$method}' was not found among the configured payment methods.");
        }

        return [
            'payments' => [['payment_method_id' => (int) $account->id, 'amount' => $totalPayment]],
            'paid' => $totalPayment,
            'ids' => (string) $account->id,
            'names' => $account->name,
        ];
    }

    /**
     * Map the caller-supplied custom payment rows onto configured accounts.
     *
     * @param  array<int, array<string, mixed>>  $customPayments
     * @param  \Illuminate\Support\Collection<int, Account>  $configured
     * @return array{payments: array<int, array{payment_method_id: int, amount: float}>, paid: float, ids: string, names: string}
     */
    private function buildCustomPayments(array $customPayments, $configured): array
    {
        if (empty($customPayments)) {
            throw new RuntimeException('At least one payment is required for a custom payment.');
        }

        $byId = $configured->keyBy('id');
        $payments = [];
        $ids = [];
        $names = [];
        $paid = 0.0;

        foreach ($customPayments as $row) {
            $id = (int) ($row['payment_method_id'] ?? 0);
            $amount = (float) ($row['amount'] ?? 0);
            $account = $byId->get($id);

            if (! $account) {
                throw new RuntimeException("Payment method #{$id} is not a configured payment method.");
            }

            $payments[] = ['payment_method_id' => $id, 'amount' => $amount];
            $ids[] = $id;
            $names[] = $account->name;
            $paid += $amount;
        }

        return [
            'payments' => $payments,
            'paid' => $paid,
            'ids' => implode(',', $ids),
            'names' => implode(', ', $names),
        ];
    }

    /**
     * The payment-method accounts configured for the business.
     *
     * @return \Illuminate\Support\Collection<int, Account>
     */
    private function configuredPaymentMethods()
    {
        return Account::query()
            ->whereIn('id', tenant_cache('payment_methods', []))
            ->get(['id', 'name']);
    }

    /**
     * Send the sale receipt over WhatsApp. Best-effort — a failure here must never
     * fail the sale, which is already committed by the time this runs.
     */
    private function dispatchWhatsapp(int $saleId): void
    {
        try {
            Sale::sendToWhatsapp($saleId);
        } catch (\Throwable $e) {
            // Receipt delivery is non-critical; the sale itself succeeded.
        }
    }
}
