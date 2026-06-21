<?php

namespace App\Actions\V1\SaleReturn;

use App\Actions\SaleReturn\Item\DeleteAction as SaleReturnItemDeleteAction;
use App\Actions\SaleReturn\UpdateAction as SaleReturnUpdateAction;
use App\Http\Requests\V1\SaleReturn\UpdateRequest;
use App\Models\Account;
use App\Models\ApiLog;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\SaleReturnPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UpdateAction
{
    /**
     * Update an existing sale return from the final data sent by the mobile app.
     *
     * Mirrors V1\SaleReturn\CreateAction, but reconciles the return against its
     * current rows: lines carrying an `id` are updated, new lines are created, and
     * lines whose id is no longer present are deleted. When capping each line at
     * what is still returnable, the return being edited is excluded from the
     * already-returned tally so its own quantity stays available. Persistence —
     * including the stock reversal / re-application and journal re-posting for an
     * already-completed return — is delegated to App\Actions\SaleReturn\UpdateAction.
     */
    public function execute(UpdateRequest $request, int $saleReturnId): SaleReturn
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

            $saleReturn = SaleReturn::query()
                ->with(['items:id,sale_return_id,sale_item_id', 'payments:id,sale_return_id'])
                ->findOrFail($saleReturnId);

            if ($saleReturn->status === 'cancelled') {
                throw new RuntimeException('A cancelled sale return can no longer be edited.');
            }

            // findOrFail respects Sale's AssignedBranchScope, so a sale outside the
            // user's branches simply isn't found.
            $sale = Sale::query()
                ->with('items')
                ->findOrFail((int) $request->validated('sale_id'));

            $accountId = $request->validated('account_id') ?? $sale->account_id;

            $items = $this->buildItems($request->validated('items'), $sale, $saleReturn);
            $totals = $this->totals($items, (float) ($request->validated('other_discount') ?? 0));

            $payment = $this->resolvePayments(
                $request->validated('paymentMethod'),
                $request->validated('payments') ?? [],
                (float) $request->validated('totalPayment'),
            );

            if ($payment['paid'] - $totals['grand_total'] > 0.01) {
                throw new RuntimeException('The refund amount cannot exceed the return total.');
            }

            $data = [
                // An edit preserves the document's status — completing or cancelling
                // a return is a separate flow.
                'status' => $saleReturn->status,
                'branch_id' => $branchId,
                'account_id' => $accountId,
                'date' => $request->validated('date') ?: $saleReturn->date,
                'gross_amount' => $totals['gross_amount'],
                'item_discount' => $totals['item_discount'],
                'tax_amount' => $totals['tax_amount'],
                // grand_total / balance are generated columns (total - other_discount,
                // grand_total - paid) so they are intentionally not set here.
                'total' => $totals['total'],
                'other_discount' => $totals['other_discount'],
                'paid' => $payment['paid'],
                'description' => $request->validated('description'),
                'items' => $items,
                'payments' => $payment['payments'],
            ];

            $saleReturn = DB::transaction(function () use ($saleReturn, $items, $data, $user) {
                $userId = (int) $user->id;

                // 1) Remove lines no longer on the return. For a completed return
                //    SaleReturn\Item\DeleteAction reverses that line's stock movement.
                $keepIds = collect($items)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
                $removedIds = $saleReturn->items
                    ->pluck('id')
                    ->reject(fn ($id) => in_array((int) $id, $keepIds, true))
                    ->all();
                foreach ($removedIds as $id) {
                    $response = (new SaleReturnItemDeleteAction())->execute($id);
                    if (! $response['success']) {
                        throw new RuntimeException($response['message']);
                    }
                }

                // 2) Replace the refund payments wholesale. We delete the rows (and
                //    their journal entries) directly rather than through
                //    SaleReturn\Payment\DeleteAction, which has a stray dd() on the
                //    completed-return path. For a completed return the whole journal
                //    is rebuilt by the web UpdateAction anyway, so this is safe.
                $paymentIds = $saleReturn->payments->pluck('id')->all();
                if ($paymentIds) {
                    JournalEntry::where('model', 'SaleReturnPayment')->whereIn('model_id', $paymentIds)->delete();
                    SaleReturnPayment::whereIn('id', $paymentIds)->delete();
                }

                // 3) Apply the update (and, for a completed return, the stock
                //    reversal / re-application + journal re-posting).
                $response = (new SaleReturnUpdateAction())->execute($data, $saleReturn->id, $userId);

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
     * Map the request lines onto the shape App\Actions\SaleReturn\UpdateAction
     * expects, resolving each line from its source sale item and capping the
     * quantity at what is still returnable — counting every non-cancelled return
     * EXCEPT the one being edited. Existing lines keep their `id`; new lines do not.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(array $lines, Sale $sale, SaleReturn $saleReturn): array
    {
        $saleItemIds = array_unique(array_map(fn ($line) => (int) $line['sale_item_id'], $lines));
        $existingReturnItemIds = $saleReturn->items->pluck('id')->map(fn ($id) => (int) $id)->all();

        $saleItems = SaleItem::query()
            ->whereIn('id', $saleItemIds)
            ->where('sale_id', $sale->id)
            ->get()
            ->keyBy('id');

        // Quantity already returned by OTHER (non-cancelled) returns — the return
        // being edited is excluded so its own lines stay fully editable.
        $alreadyReturned = SaleReturnItem::query()
            ->whereIn('sale_item_id', $saleItemIds)
            ->where('sale_return_id', '!=', $saleReturn->id)
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

            $discount = isset($line['discount'])
                ? (float) $line['discount']
                : ($saleItem->quantity > 0 ? round((float) $saleItem->discount * $quantity / (float) $saleItem->quantity, 2) : 0.0);

            $item = [
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

            // Only trust an id that actually belongs to this return.
            if (isset($line['id']) && in_array((int) $line['id'], $existingReturnItemIds, true)) {
                $item['id'] = (int) $line['id'];
            }

            $items[] = $item;
        }

        return $items;
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
            ->whereIn('id', cache('payment_methods', []))
            ->get(['id', 'name']);
    }

    /**
     * Persist an api_logs row at request entry so every Sale Return API call is auditable.
     */
    private function startApiLog(UpdateRequest $request): ?ApiLog
    {
        try {
            return ApiLog::create([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'service_name' => 'Sale Return Update',
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
