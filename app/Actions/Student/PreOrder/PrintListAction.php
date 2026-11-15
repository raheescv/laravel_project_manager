<?php

namespace App\Actions\Student\PreOrder;

use App\Models\Branch;
use App\Support\Student\StudentSettings;
use Illuminate\Support\Carbon;

/**
 * What the printed pre-order list for one day shows (print/student/pre-orders),
 * branch by branch, since each branch's kitchen makes and hands over its own
 * students' meals: per branch, what to prepare, the paid orders grouped by
 * school, grade and class for delivering, and the unpaid ones. [$branchId]
 * prints one branch only.
 *
 * Read-only, so it returns the value directly.
 */
class PrintListAction
{
    /**
     * @param  string  $date  YYYY-MM-DD, as it comes in the URL
     * @return array{day: Carbon, summary: array, sections: list<array{branch: string, students: int, unpaid: int, items: list<array{name: string, quantity: int}>, groups: \Illuminate\Support\Collection, unpaidOrders: \Illuminate\Support\Collection}>, cutoff: string, locked: bool}
     */
    public function execute(string $date, ?int $branchId = null): array
    {
        $day = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $summary = (new SummaryAction())->execute($day);
        $settings = StudentSettings::current();
        $branches = Branch::query()->get(['id', 'name', 'location'])->keyBy('id');

        $sections = collect($summary['orders'])
            ->when($branchId, fn ($orders) => $orders->where('branch_id', $branchId))
            ->groupBy(fn (array $order) => (int) $order['branch_id'])
            ->sortBy(fn ($orders, $id) => $branches->get($id)?->name ?? 'zzz')
            ->map(function ($orders, $id) use ($branches) {
                $paid = $orders->where('paid', true);

                return [
                    'branch' => $branches->get($id)?->name ?? 'No branch',
                    'students' => $paid->count(),
                    'unpaid' => $orders->count() - $paid->count(),
                    'items' => $paid->flatMap(fn (array $order) => $order['items'])->groupBy('name')
                        ->map(fn ($lines, $name) => ['name' => $name, 'quantity' => (int) $lines->sum('quantity')])
                        ->sortByDesc('quantity')->values()->all(),
                    'groups' => $paid->groupBy(fn (array $order) => ($order['school'] ?: 'No school').' · '.self::gradeClass($order)),
                    'unpaidOrders' => $orders->where('paid', false)->values(),
                ];
            })
            ->values()
            ->all();

        return [
            'day' => $day,
            'summary' => $summary,
            'sections' => $sections,
            'branchName' => $branchId ? ($branches->get($branchId)?->name ?? null) : null,
            'cutoff' => $settings->preOrderCutoffLabel(),
            'locked' => $settings->preOrderLocked($day),
        ];
    }

    /**
     * "Grade 4 · Class A", spelled out so whoever delivers can find the room. A grade
     * or class already saved with its word ("Grade 5") is not given it twice.
     *
     * @param  array{grade: ?string, section: ?string}  $order
     */
    public static function gradeClass(array $order): string
    {
        $grade = trim((string) ($order['grade'] ?? ''));
        $section = mb_strtoupper(trim((string) ($order['section'] ?? '')));

        return collect([
            $grade === '' ? null : (preg_match('/^grade\b/i', $grade) ? $grade : 'Grade '.$grade),
            $section === '' ? null : (preg_match('/^class\b/i', $section) ? $section : 'Class '.$section),
        ])->filter()->implode(' · ') ?: 'No grade';
    }
}
