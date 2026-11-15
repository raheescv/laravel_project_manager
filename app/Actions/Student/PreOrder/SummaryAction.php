<?php

namespace App\Actions\Student\PreOrder;

use App\Models\Product;
use App\Models\StudentPreOrder;
use App\Models\StudentPreOrderCollection;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Carbon;

/**
 * The pre-orders for [$date], as the kitchen and the canteen counter need them:
 * every child with their school, grade, class and registration number, what they
 * ordered, and whether it is paid (the sale number) or not. Item totals count paid
 * orders only, since an unpaid order is not served.
 *
 * Which order applies is ForDayAction::allOn(). Read-only, so it returns the value directly.
 */
class SummaryAction
{
    /**
     * @return array{
     *     date: string,
     *     orders: list<array{student: string, branch_id: ?int, admission_no: ?string, school: ?string, grade: ?string, section: ?string, class: string, source: string, note: ?string, paid: bool, invoice_no: ?string, items: list<array{name: string, quantity: int, price: float}>, total: float}>,
     *     items: list<array{name: string, quantity: int, amount: float}>,
     *     students: int,
     *     unpaid: int,
     *     total: float
     * }
     */
    public function execute(Carbon $date): array
    {
        $resolved = ForDayAction::allOn($date);
        $resolved->load(['items', 'account:id,name', 'account.studentDetail:id,account_id,admission_no,branch_id,grade,section,status', 'account.studentDetail.branch:id,name,location']);
        $fallbackBranch = StudentSettings::current()->preOrderBranch();

        $paid = StudentPreOrderCollection::query()
            ->whereIn('student_pre_order_id', $resolved->modelKeys())
            ->whereDate('date', $date->toDateString())
            ->with('sale:id,invoice_no,status,grand_total,branch_id')
            ->get()
            ->filter(fn (StudentPreOrderCollection $collection) => $collection->sale?->status === 'completed')
            ->keyBy('student_pre_order_id');

        $products = Product::query()
            ->whereIn('id', $resolved->flatMap(fn (StudentPreOrder $order) => $order->items->pluck('product_id'))->unique())
            ->get(['id', 'name', 'mrp'])
            ->keyBy('id');

        $orders = $resolved
            ->filter(fn (StudentPreOrder $order) => $order->account && $order->items->isNotEmpty())
            ->map(function (StudentPreOrder $order) use ($products, $paid, $fallbackBranch) {
                $detail = $order->account->studentDetail;
                $sale = $paid->get($order->id)?->sale;
                $items = $order->items->map(fn ($item) => [
                    'name' => $products->get($item->product_id)->name ?? 'Item no longer sold',
                    'quantity' => (int) $item->quantity,
                    'price' => round((float) ($products->get($item->product_id)->mrp ?? 0), 2),
                ])->values();

                return [
                    'student' => $order->account->name,
                    // Where the meal is made and handed over: the branch the sale was booked at, or for an
                    // unpaid order the branch it would be booked at (the student's own, else the canteen setting).
                    'branch_id' => (int) ($sale?->branch_id ?? $detail?->branch_id ?? $fallbackBranch) ?: null,
                    'admission_no' => $detail?->admission_no,
                    'school' => $detail?->school,
                    'grade' => $detail?->grade,
                    'section' => $detail?->section,
                    'class' => $detail?->classLabel() ?? '',
                    'source' => $order->type,
                    'note' => $order->note,
                    'paid' => (bool) $sale,
                    'invoice_no' => $sale?->invoice_no,
                    'items' => $items->all(),
                    'total' => $sale ? round((float) $sale->grand_total, 2) : round($items->sum(fn ($item) => $item['price'] * $item['quantity']), 2),
                ];
            })
            ->sortBy([['paid', 'desc'], ['school', 'asc'], ['grade', 'asc'], ['section', 'asc'], ['student', 'asc']])
            ->values();

        $served = $orders->where('paid', true);
        $items = $served->flatMap(fn (array $order) => $order['items'])
            ->groupBy('name')
            ->map(fn ($lines, $name) => [
                'name' => $name,
                'quantity' => (int) $lines->sum('quantity'),
                'amount' => round($lines->sum(fn ($line) => $line['price'] * $line['quantity']), 2),
            ])
            ->sortByDesc('quantity')
            ->values();

        return [
            'date' => $date->toDateString(),
            'orders' => $orders->all(),
            'items' => $items->all(),
            'students' => $served->count(),
            'unpaid' => $orders->count() - $served->count(),
            'total' => round($served->sum('total'), 2),
        ];
    }
}
