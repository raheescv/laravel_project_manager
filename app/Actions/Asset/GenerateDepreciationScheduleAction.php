<?php

namespace App\Actions\Asset;

use App\Models\AssetDepreciationSchedule;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateDepreciationScheduleAction
{
    public function execute(Product $asset, ?int $userId = null): array
    {
        try {
            if ($asset->type !== 'asset') {
                throw new \Exception('Depreciation schedules can only be generated for assets.');
            }

            $cost = (float) ($asset->cost ?? 0);
            $duration = (float) ($asset->duration ?? 0);
            if ($cost <= 0 || $duration <= 0 || empty($asset->purchase_date)) {
                AssetDepreciationSchedule::where('product_id', $asset->id)->where('status', '!=', 'posted')->forceDelete();

                return ['success' => true, 'message' => 'Skipped schedule generation until asset purchase details are complete.'];
            }

            $totalPeriods = $this->getTotalPeriods($asset);
            $periodType = $this->resolvePeriodType($asset->duration_period);
            $startDate = Carbon::parse($asset->prorata_date ?: $asset->purchase_date)->startOfDay();

            $postedSchedules = AssetDepreciationSchedule::where('product_id', $asset->id)
                ->posted()
                ->orderBy('period_no')
                ->get();

            $postedCount = $postedSchedules->count();
            $openingBookValue = $postedSchedules->last()?->closing_book_value ?? $cost;
            $accumulatedDepreciation = $postedSchedules->last()?->accumulated_depreciation ?? 0;

            DB::transaction(function () use ($asset, $userId, $totalPeriods, $periodType, $startDate, $postedCount, &$openingBookValue, &$accumulatedDepreciation): void {
                AssetDepreciationSchedule::where('product_id', $asset->id)
                    ->where('status', '!=', 'posted')
                    ->forceDelete();

                $rows = [];
                for ($periodNo = $postedCount + 1; $periodNo <= $totalPeriods; $periodNo++) {
                    $depreciationAmount = $this->calculatePeriodAmount(
                        $asset,
                        $openingBookValue,
                        $accumulatedDepreciation,
                        $periodNo,
                        $totalPeriods
                    );

                    $closingBookValue = round(max($openingBookValue - $depreciationAmount, 0), 2);
                    $accumulatedDepreciation = round(min($asset->cost, $accumulatedDepreciation + $depreciationAmount), 2);

                    $rows[] = [
                        'tenant_id' => $asset->tenant_id,
                        'branch_id' => session('branch_id'),
                        'product_id' => $asset->id,
                        'period_no' => $periodNo,
                        'period_type' => $periodType,
                        'schedule_date' => $this->resolveScheduleDate($startDate, $periodNo, $asset->duration_period)->toDateString(),
                        'opening_book_value' => round($openingBookValue, 2),
                        'depreciation_amount' => round($depreciationAmount, 2),
                        'accumulated_depreciation' => $accumulatedDepreciation,
                        'closing_book_value' => $closingBookValue,
                        'status' => 'pending',
                        'created_by' => $userId,
                        'updated_by' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $openingBookValue = $closingBookValue;
                }

                if ($rows) {
                    AssetDepreciationSchedule::insert($rows);
                }
            });

            return ['success' => true, 'message' => 'Depreciation schedule generated successfully.'];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    protected function getTotalPeriods(Product $asset): int
    {
        $duration = (float) ($asset->duration ?? 0);

        return max((int) ceil($duration), 1);
    }

    protected function resolvePeriodType(?string $durationPeriod): string
    {
        return match ($durationPeriod) {
            'days' => 'daily',
            'years' => 'yearly',
            default => 'monthly',
        };
    }

    protected function resolveScheduleDate(Carbon $startDate, int $periodNo, ?string $durationPeriod): Carbon
    {
        $offset = $periodNo - 1;

        return match ($durationPeriod) {
            'days' => $startDate->copy()->addDays($offset),
            'months' => $startDate->copy()->addMonthsNoOverflow($offset),
            default => $startDate->copy()->addYearsNoOverflow($offset),
        };
    }

    protected function calculatePeriodAmount(Product $asset, float $openingBookValue, float $accumulatedDepreciation, int $periodNo, int $totalPeriods): float
    {
        $remainingDepreciable = round(max(((float) $asset->cost) - $accumulatedDepreciation, 0), 2);
        if ($remainingDepreciable <= 0) {
            return 0;
        }

        if ($periodNo === $totalPeriods) {
            return $remainingDepreciable;
        }

        if (($asset->depreciation_method ?? 'straight_line') === 'declining_balance') {
            $factor = (float) ($asset->declining_factor ?: 2.0);
            $rate = $factor / max($totalPeriods, 1);

            return round(min($remainingDepreciable, max($openingBookValue * $rate, 0)), 2);
        }

        $remainingPeriods = max($totalPeriods - $periodNo + 1, 1);

        return round($remainingDepreciable / $remainingPeriods, 2);
    }
}
