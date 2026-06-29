<?php

namespace App\Actions\V1\Sale;

use App\Actions\Account\CreateAction as AccountCreateAction;
use App\Actions\Sale\Item\DeleteAction as SaleItemDeleteAction;
use App\Actions\Sale\Payment\DeleteAction as SalePaymentDeleteAction;
use App\Actions\Sale\UpdateAction as SaleUpdateAction;
use App\Http\Requests\V1\Sale\UpdateRequest;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\ApiLog;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpdateAction
{
    /**
     * Update an existing sale from the final data sent by the mobile app.
     *
     * Mirrors V1\Sale\CreateAction, but reconciles the sale against its current
     * rows: lines carrying an `id` are updated, new lines are created, and lines
     * whose id is no longer present are deleted. Persistence — including the stock
     * reversal / re-application and journal re-posting for an already-completed
     * sale — is delegated to the existing App\Actions\Sale\UpdateAction so an edit
     * behaves exactly as it does on the web POS.
     */
    public function execute(UpdateRequest $request, int $saleId): Sale
    {
        $apiLog = $this->startApiLog($request);

        try {
            $user = $request->user();
            // The delegated web actions gate completed-edits on Auth::user()->can(...)
            // and stamp Auth::id(); make sure the guard resolves to the API user.
            Auth::setUser($user);

            $branchId = $user->default_branch_id;

            if (! $branchId) {
                throw new RuntimeException('Your account is not assigned to a branch.');
            }

            // findOrFail respects Sale's AssignedBranchScope, so a sale outside the
            // user's branches simply isn't found.
            $sale = Sale::query()
                ->with(['items:id,sale_id,product_id', 'payments:id,sale_id'])
                ->findOrFail($saleId);

            if ($sale->status === 'cancelled') {
                throw new RuntimeException('A cancelled sale can no longer be edited.');
            }

            $customer = $this->resolveCustomer($request->validated('customerName'), $request->validated('phoneNumber'));
            $items = $this->buildItems($request->validated('items'), $sale, $branchId, (int) $user->id);
            $totalPayment = (float) $request->validated('totalPayment');
            $payment = $this->resolvePayments(
                $request->validated('paymentMethod'),
                $request->validated('payments') ?? [],
                $totalPayment,
            );

            $totals = $this->totals($items, (float) ($request->validated('discount') ?? 0));

            $data = [
                // An edit preserves the document's status — it does not complete a
                // draft or cancel a sale (those are separate flows).
                'status' => $sale->status,
                'branch_id' => $branchId,
                'account_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_mobile' => $customer->mobile,
                'sale_type' => $sale->sale_type ?: 'normal',
                'date' => $sale->date,
                'gross_amount' => $totals['gross_amount'],
                'item_discount' => $totals['item_discount'],
                'tax_amount' => $totals['tax_amount'],
                'other_discount' => $totals['other_discount'],
                'total' => $totals['total'],
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

            $sale = DB::transaction(function () use ($sale, $items, $data, $user) {
                $userId = (int) $user->id;

                // 1) Remove lines that are no longer on the ticket. For a completed
                //    sale Sale\Item\DeleteAction reverses that line's stock movement.
                $keepIds = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
                $removedIds = $sale->items
                    ->pluck('id')
                    ->reject(fn ($id) => in_array((int) $id, $keepIds, true))
                    ->all();
                foreach ($removedIds as $id) {
                    $response = (new SaleItemDeleteAction())->execute($id);
                    if (! $response['success']) {
                        throw new RuntimeException($response['message']);
                    }
                }

                // 2) Replace the payments wholesale — the app re-sends the entire
                //    breakdown, so the existing rows are dropped and recreated. The
                //    web UpdateAction re-posts the journal from the new payments.
                foreach ($sale->payments->pluck('id') as $id) {
                    $response = (new SalePaymentDeleteAction())->execute($id);
                    if (! $response['success']) {
                        throw new RuntimeException($response['message']);
                    }
                }

                // 3) Apply the update (and, for a completed sale, the stock
                //    reversal / re-application + journal re-posting).
                $response = (new SaleUpdateAction())->execute($data, $sale->id, $userId);

                if (! $response['success']) {
                    throw new RuntimeException($response['message']);
                }

                return $response['data'];
            })->load([
                'items.product:id,name,code,name_arabic,type',
                'items.employee:id,name',
                'payments.paymentMethod:id,name',
                'account:id,name,mobile',
                'createdUser:id,name',
                'branch',
            ]);

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
     * Map the request line items to the shape App\Actions\Sale\UpdateAction
     * expects, resolving each product's inventory at the user's branch. Existing
     * lines keep their `id` (so the row is updated); new lines have none.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(array $lines, Sale $sale, int $branchId, int $userId): array
    {
        $productIds = array_unique(array_map(fn ($line) => (int) $line['productId'], $lines));
        $existingItemIds = $sale->items->pluck('id')->map(fn ($id) => (int) $id)->all();

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

            $item = [
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

            // Only trust an id that actually belongs to this sale; anything else is
            // treated as a new line rather than silently patching a foreign row.
            if (isset($line['id']) && in_array((int) $line['id'], $existingItemIds, true)) {
                $item['id'] = (int) $line['id'];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Compute the sale header totals from the resolved line items.
     *
     * `total` excludes the order-level discount on purpose: the sales table's
     * grand_total is a generated column — `(total - other_discount + freight) +
     * round_off` — so subtracting other_discount here would double-count it.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{gross_amount: float, item_discount: float, tax_amount: float, other_discount: float, total: float}
     */
    private function totals(array $items, float $otherDiscount): array
    {
        $gross = 0.0;
        $itemDiscount = 0.0;
        $taxAmount = 0.0;

        foreach ($items as $item) {
            $lineGross = (float) $item['unit_price'] * (float) $item['quantity'];
            $net = $lineGross - (float) $item['discount'];

            $gross += $lineGross;
            $itemDiscount += (float) $item['discount'];
            $taxAmount += $net * (float) $item['tax'] / 100;
        }

        $gross = round($gross, 2);
        $itemDiscount = round($itemDiscount, 2);
        $taxAmount = round($taxAmount, 2);
        $otherDiscount = round($otherDiscount, 2);

        return [
            'gross_amount' => $gross,
            'item_discount' => $itemDiscount,
            'tax_amount' => $taxAmount,
            'other_discount' => $otherDiscount,
            'total' => round($gross - $itemDiscount + $taxAmount, 2),
        ];
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
     * Persist an api_logs row at request entry so every Sale API call is auditable.
     */
    private function startApiLog(UpdateRequest $request): ?ApiLog
    {
        try {
            return ApiLog::create([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'service_name' => 'Sale Update',
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
            $apiLog->update([
                'status' => $status,
                'response' => $response ? json_encode($response) : null,
                'description' => $description,
            ]);
        } catch (\Throwable $e) {
            // Logging must never mask the real outcome of the request.
        }
    }
}
