<?php

namespace App\Actions\V1\SaleReturn;

use App\Actions\SaleReturn\CreateAction as SaleReturnCreateAction;
use App\Http\Requests\V1\SaleReturn\StoreRequest;
use App\Models\Account;
use App\Models\ApiLog;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateAction
{
    /**
     * How recent an identical return must be to be treated as an accidental duplicate.
     */
    private const DUPLICATE_WINDOW_MINUTES = 2;

    /**
     * Create a sale return from the final data sent by the mobile app.
     *
     * Persistence is delegated to the existing App\Actions\SaleReturn\CreateAction
     * so that stock movements and journal postings run exactly as they do for the
     * web sale-return screen.
     */
    public function execute(StoreRequest $request): SaleReturn
    {
        $apiLog = $this->startApiLog($request);

        try {
            $user = $request->user();
            $branchId = $user->default_branch_id;

            if (! $branchId) {
                throw new RuntimeException('Your account is not assigned to a branch.');
            }

            // findOrFail respects Sale's AssignedBranchScope, so a sale outside the
            // user's branches simply isn't found.
            $sale = Sale::query()
                ->with('items')
                ->findOrFail((int) $request->validated('sale_id'));

            $accountId = $request->validated('account_id') ?? $sale->account_id;

            $items = $this->buildItems($request->validated('items'), $sale);
            $totals = $this->totals($items, (float) ($request->validated('other_discount') ?? 0));

            $payment = $this->resolvePayments(
                $request->validated('paymentMethod'),
                $request->validated('payments') ?? [],
                (float) $request->validated('totalPayment'),
            );

            if ($payment['paid'] - $totals['grand_total'] > 0.01) {
                throw new RuntimeException('The refund amount cannot exceed the return total.');
            }

            $status = $request->validated('status') ?: 'completed';

            if ($status === 'completed') {
                $this->guardAgainstDuplicate((int) $accountId, (int) $user->id, $items);
            }

            $data = [
                'status' => $status,
                'branch_id' => $branchId,
                'account_id' => $accountId,
                'date' => $request->validated('date') ?: today()->toDateString(),
                'reference_no' => $request->validated('reference_no'),
                'gross_amount' => $totals['gross_amount'],
                'item_discount' => $totals['item_discount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'other_discount' => $totals['other_discount'],
                'paid' => $payment['paid'],
                'description' => $request->validated('description'),
                'items' => $items,
                'payments' => $payment['payments'],
            ];

            $saleReturn = DB::transaction(function () use ($data, $user) {
                $response = (new SaleReturnCreateAction())->execute($data, (int) $user->id);

                if (! $response['success']) {
                    throw new RuntimeException($response['message']);
                }

                return $response['data'];
            })->load([
                'items.product:id,name,name_arabic,type',
                'items.employee:id,name',
                'payments.paymentMethod:id,name',
                'account:id,name,mobile',
                'createdUser:id,name',
                'branch',
            ]);

            $this->completeApiLog($apiLog, 'success', [
                'sale_return_id' => $saleReturn->id,
                'sale_id' => $sale->id,
            ]);

            return $saleReturn;
        } catch (\Throwable $e) {
            $this->completeApiLog($apiLog, 'failed', null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Map the request lines onto the shape App\Actions\SaleReturn\CreateAction
     * expects, resolving each line from its source sale item and capping the
     * quantity at what is still returnable.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(array $lines, Sale $sale): array
    {
        $saleItemIds = array_unique(array_map(fn ($line) => (int) $line['sale_item_id'], $lines));

        $saleItems = SaleItem::query()
            ->whereIn('id', $saleItemIds)
            ->where('sale_id', $sale->id)
            ->get()
            ->keyBy('id');

        $alreadyReturned = SaleReturnItem::query()
            ->whereIn('sale_item_id', $saleItemIds)
            ->whereHas('saleReturn', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->selectRaw('sale_item_id, COALESCE(SUM(quantity), 0) as qty')
            ->groupBy('sale_item_id')
            ->pluck('qty', 'sale_item_id');

        $items = [];

        foreach ($lines as $line) {
            $saleItemId = (int) $line['sale_item_id'];
            $saleItem = $saleItems->get($saleItemId);

            if (! $saleItem) {
                throw new RuntimeException("Line item #{$saleItemId} does not belong to this sale.");
            }

            $quantity = (float) $line['quantity'];
            $returnable = max(0, round((float) $saleItem->quantity - (float) ($alreadyReturned[$saleItemId] ?? 0), 3));

            if ($quantity > $returnable + 0.0001) {
                $name = $saleItem->product?->name ?? "item #{$saleItemId}";
                throw new RuntimeException("Return quantity for {$name} cannot exceed the remaining {$returnable}.");
            }

            $unitPrice = isset($line['unitPrice']) ? (float) $line['unitPrice'] : (float) $saleItem->unit_price;

            // Prorate the original line discount to the returned quantity unless the
            // caller supplies an explicit discount for the line.
            $discount = isset($line['discount'])
                ? (float) $line['discount']
                : ($saleItem->quantity > 0 ? round((float) $saleItem->discount * $quantity / (float) $saleItem->quantity, 2) : 0.0);

            $items[] = [
                'sale_item_id' => $saleItem->id,
                'inventory_id' => $saleItem->inventory_id,
                'product_id' => $saleItem->product_id,
                'employee_id' => $saleItem->employee_id,
                'unit_id' => $saleItem->unit_id ?: 1,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'conversion_factor' => $saleItem->conversion_factor ?: 1,
                'discount' => $discount,
                'tax' => (float) $saleItem->tax,
            ];
        }

        return $items;
    }

    /**
     * Reject a return that is identical to one this user has just created against
     * the same sale, guarding against double taps and network retries from the app.
     *
     * The buildItems() quantity cap already stops a *second, separate* return from
     * over-returning, but it cannot see a retry of the request currently in flight.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function guardAgainstDuplicate(int $accountId, int $userId, array $items): void
    {
        $signature = $this->itemsSignature($items);

        // sale_returns carries no sale_id — the link to the sale lives on
        // sale_return_items.sale_item_id, and those ids are what the signature
        // compares, so matching on the account plus the signature is exact.
        $recentReturns = SaleReturn::query()
            ->where('account_id', $accountId)
            ->where('created_by', $userId)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMinutes(self::DUPLICATE_WINDOW_MINUTES))
            ->with('items:id,sale_return_id,sale_item_id,quantity,unit_price,discount')
            ->latest('id')
            ->get();

        foreach ($recentReturns as $saleReturn) {
            $existing = $saleReturn->items
                ->map(fn ($item) => [
                    'sale_item_id' => $item->sale_item_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount ?? 0,
                ])
                ->all();

            if ($this->itemsSignature($existing) === $signature) {
                throw new RuntimeException("This return was already saved a moment ago (matches return #{$saleReturn->id}). Please refresh before trying again.");
            }
        }
    }

    /**
     * Build an order-independent fingerprint of the return line items.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function itemsSignature(array $items): string
    {
        $rows = array_map(
            fn ($item) => [
                'sale_item_id' => (int) $item['sale_item_id'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'discount' => (float) ($item['discount'] ?? 0),
            ],
            $items,
        );

        usort($rows, fn ($a, $b) => $a['sale_item_id'] <=> $b['sale_item_id']);

        return md5((string) json_encode($rows));
    }

    /**
     * Compute the sale-return header totals from the resolved line items.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{gross_amount: float, item_discount: float, tax_amount: float, total: float, other_discount: float, grand_total: float}
     */
    private function totals(array $items, float $otherDiscount): array
    {
        $gross = 0.0;
        $itemDiscount = 0.0;
        $taxAmount = 0.0;
        $total = 0.0;

        foreach ($items as $item) {
            $lineGross = (float) $item['unit_price'] * (float) $item['quantity'];
            $net = $lineGross - (float) $item['discount'];
            $lineTax = $net * (float) $item['tax'] / 100;

            $gross += $lineGross;
            $itemDiscount += (float) $item['discount'];
            $taxAmount += $lineTax;
            $total += $net + $lineTax;
        }

        $gross = round($gross, 2);
        $total = round($total, 2);
        $otherDiscount = round($otherDiscount, 2);

        return [
            'gross_amount' => $gross,
            'item_discount' => round($itemDiscount, 2),
            'tax_amount' => round($taxAmount, 2),
            'total' => $total,
            'other_discount' => $otherDiscount,
            'grand_total' => round($total - $otherDiscount, 2),
        ];
    }

    /**
     * Resolve the refund breakdown, mirroring the Sale API contract:
     *   - "credit" → no refund payment is recorded (paid = 0).
     *   - "custom" → the caller supplies one or more {payment_method_id, amount} rows.
     *   - any other value → treated as a method NAME, refunded in full to that account.
     *
     * @param  array<int, array<string, mixed>>  $customPayments
     * @return array{payments: array<int, array{payment_method_id: int, amount: float}>, paid: float}
     */
    private function resolvePayments(string $method, array $customPayments, float $totalPayment): array
    {
        $method = trim($method);

        if (strcasecmp($method, 'credit') === 0) {
            return ['payments' => [], 'paid' => 0.0];
        }

        $configured = $this->configuredPaymentMethods();

        if ($configured->isEmpty()) {
            throw new RuntimeException('No payment methods are configured for this business.');
        }

        if (strcasecmp($method, 'custom') === 0) {
            if (empty($customPayments)) {
                throw new RuntimeException('At least one payment is required for a custom refund.');
            }

            $byId = $configured->keyBy('id');
            $payments = [];
            $paid = 0.0;

            foreach ($customPayments as $row) {
                $id = (int) ($row['payment_method_id'] ?? 0);
                $amount = (float) ($row['amount'] ?? 0);

                if (! $byId->get($id)) {
                    throw new RuntimeException("Payment method #{$id} is not a configured payment method.");
                }

                $payments[] = ['payment_method_id' => $id, 'amount' => $amount];
                $paid += $amount;
            }

            return ['payments' => $payments, 'paid' => round($paid, 2)];
        }

        $account = $configured->first(fn (Account $a) => stripos($a->name, $method) !== false);

        if (! $account) {
            throw new RuntimeException("Payment method '{$method}' was not found among the configured payment methods.");
        }

        return [
            'payments' => [['payment_method_id' => (int) $account->id, 'amount' => $totalPayment]],
            'paid' => $totalPayment,
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
     * Persist an api_logs row at request entry so every Sale Return API call is auditable.
     */
    private function startApiLog(StoreRequest $request): ?ApiLog
    {
        try {
            return ApiLog::create([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'service_name' => 'Sale Return Create',
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
