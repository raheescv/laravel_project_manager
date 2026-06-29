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
            $user = $request->user();
            $branchId = $user->default_branch_id;

            if (! $branchId) {
                throw new RuntimeException('Your account is not assigned to a branch.');
            }

            $customer = $this->resolveCustomer($request->validated('customerName'), $request->validated('phoneNumber'));
            $items = $this->buildItems($request->validated('items'), $branchId, (int) $user->id);
            $totalPayment = (float) $request->validated('totalPayment');
            $payment = $this->resolvePayments(
                $request->validated('paymentMethod'),
                $request->validated('payments') ?? [],
                $totalPayment,
            );

            $this->guardAgainstDuplicate($branchId, (int) $customer->id, (int) $user->id, $items, $payment['paid']);

            $data = [
                'status' => 'completed',
                'branch_id' => $branchId,
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
            $this->completeApiLog($apiLog, 'failed', null, $e->getMessage());
            throw $e;
        }
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
            ->whereIn('id', cache('payment_methods', []))
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
