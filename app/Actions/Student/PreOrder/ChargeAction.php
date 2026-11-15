<?php

namespace App\Actions\Student\PreOrder;

use App\Actions\Sale\CreateAction as SaleCreateAction;
use App\Actions\Student\GetBalanceAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Scopes\AssignedBranchScope;
use App\Models\StudentPreOrder;
use App\Models\StudentPreOrderCollection;
use App\Models\User;
use App\Support\Student\StudentSettings;
use Exception;
use Illuminate\Support\Carbon;

/**
 * Pay for a pre-order on one day from the student's card: a completed sale, dated
 * the day the meal is served and booked at the student's own branch (picked when
 * registering), or the pre-order branch in Settings → Student Cards → Canteen for
 * a student without one, paid with the Student Wallet. The collection row ties
 * the order and the day to that sale, which is how "paid" is known everywhere.
 *
 * A pre-order is paid only from money already on the card: the balance must
 * cover the whole order, and the school's overdraft does not count (the till's
 * card guard would allow it). A short card leaves the parent the bank card.
 * [$funded]: a bank card payment of exactly this order just landed on the card
 * (QPay\SettleAction), so it pays the order whatever the card owed before. This
 * action is the only money check for a pre-order sale; GuardCardPaymentAction
 * leaves `pre_order` sales to it. The sale goes through the normal sale action,
 * so stock and the books move exactly as for a till sale. The caller owns the
 * transaction.
 */
class ChargeAction
{
    public function execute(StudentPreOrder $order, Carbon $date, ?int $userId = null, bool $funded = false): array
    {
        try {
            $settings = StudentSettings::current();
            $date = $date->copy()->startOfDay();

            if (StudentPreOrderCollection::where('student_pre_order_id', $order->id)->whereDate('date', $date->toDateString())->exists()) {
                throw new Exception('This pre-order is already paid for '.$date->format('D j M').'.', 1);
            }

            $student = Account::query()->whereKey($order->account_id)->firstOrFail();
            // The student's own branch (the school picked when registering), else the canteen setting.
            $ownBranch = $student->studentDetail?->branch_id;
            $branchId = ($ownBranch && Branch::query()->whereKey($ownBranch)->exists() ? (int) $ownBranch : null)
                ?? $settings->preOrderBranch()
                ?? throw new Exception('Choose the branch for pre-order sales in Settings → Student Cards → Canteen.', 1);
            $recorder = $settings->preOrderUserId ?? User::systemUserId((int) $order->tenant_id) ?? $userId;
            if (! $recorder || ! User::query()->whereKey($recorder)->exists()) {
                throw new Exception('Choose the user for pre-order sales in Settings → Student Cards → Canteen.', 1);
            }

            $lines = $this->lines($order, $branchId, $recorder);
            $total = round(array_sum(array_map(fn (array $line) => $line['total'], $lines)), 2);

            if (! $funded && (new GetBalanceAction())->execute($student->id) + 0.001 < $total) {
                throw new Exception($this->reason('balance', $student->name, $total), 1);
            }

            $response = (new SaleCreateAction())->execute([
                'status' => 'completed',
                'source' => 'pre_order',
                'branch_id' => $branchId,
                'account_id' => $student->id,
                'customer_name' => $student->name,
                'customer_mobile' => $student->mobile,
                'sale_type' => 'normal',
                'date' => $date->toDateString(),
                'reference_no' => 'PRE-'.$order->id,
                'notes' => $order->note ? 'Pre-order note: '.$order->note : null,
                'gross_amount' => 0,
                'item_discount' => 0,
                'tax_amount' => 0,
                'other_discount' => 0,
                'freight' => 0,
                'round_off' => 0,
                'paid' => $total,
                'items' => array_map(fn (array $line) => array_diff_key($line, ['total' => true]), $lines),
                'payments' => [['payment_method_id' => Account::idBySlug('student_card'), 'amount' => $total]],
                'comboOffers' => [],
            ], $recorder);

            if (! $response['success']) {
                throw new Exception($this->reason($response['message'], $student->name, $total), 1);
            }

            $collection = StudentPreOrderCollection::create([
                'student_pre_order_id' => $order->id,
                'account_id' => $order->account_id,
                'date' => $date->toDateString(),
                'sale_id' => $response['data']->id,
                'created_by' => $userId,
            ]);

            $return['success'] = true;
            $return['message'] = 'Pre-order paid';
            $return['data'] = $collection->setRelation('sale', $response['data']);
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /** What paying for [$order] costs now: the same lines and prices the sale will have. */
    public static function amountFor(StudentPreOrder $order): float
    {
        $order->loadMissing('items');
        $products = MenuAction::query(StudentSettings::current())->whereIn('id', $order->items->pluck('product_id'))->get()->keyBy('id');

        return round($order->items->sum(function ($item) use ($products) {
            $product = $products->get($item->product_id);

            return $product ? self::lineTotal($product, (int) $item->quantity) : 0;
        }), 2);
    }

    /** Quantity × selling price, plus the product's tax on that. */
    private static function lineTotal(Product $product, int $quantity): float
    {
        $net = round((float) $product->mrp * $quantity, 2);

        return $net + round($net * (float) ($product->tax ?? 0) / 100, 2);
    }

    /**
     * The order's items as sale lines at today's selling price, from the pre-order
     * branch's stock. Items taken off the menu since are dropped; none left is an error.
     *
     * @return list<array<string, mixed>>
     */
    private function lines(StudentPreOrder $order, int $branchId, int $recorder): array
    {
        $order->loadMissing('items');
        $products = MenuAction::query(StudentSettings::current())->whereIn('id', $order->items->pluck('product_id'))->get()->keyBy('id');
        $inventories = Inventory::withoutGlobalScope(AssignedBranchScope::class)
            ->whereIn('product_id', $products->keys())
            ->where('branch_id', $branchId)
            ->whereNull('employee_id')
            ->pluck('id', 'product_id');

        $lines = [];
        foreach ($order->items as $item) {
            $product = $products->get($item->product_id);
            if (! $product) {
                continue;
            }
            if (! $inventories->has($product->id)) {
                throw new Exception($product->name.' is not stocked at the pre-order branch.', 1);
            }
            $lines[] = [
                'employee_id' => $recorder,
                'inventory_id' => $inventories->get($product->id),
                'product_id' => $product->id,
                'unit_id' => $product->unit_id,
                'unit_price' => round((float) $product->mrp, 2),
                'quantity' => $item->quantity,
                'conversion_factor' => 1,
                'discount' => 0,
                'tax' => (float) ($product->tax ?? 0),
                'total' => self::lineTotal($product, (int) $item->quantity),
            ];
        }

        if (! $lines) {
            throw new Exception('None of the items in this order are on the menu any more.', 1);
        }

        return $lines;
    }

    /** The card guard speaks to cashiers; a parent needs to know what to do. */
    private function reason(string $message, string $name, float $total): string
    {
        return str_contains(strtolower($message), 'balance') || str_contains(strtolower($message), 'overdraft')
            ? "There isn't enough on {$name}'s card for this order (".number_format($total, 2).'). Please top up, or pay by bank card.'
            : $message;
    }
}
