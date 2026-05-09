<?php

namespace App\Actions\Asset;

use App\Actions\Journal\DeleteAction as JournalDeleteAction;
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
                $this->deleteUnpostedSchedules($asset->id, $userId, 0);

                return ['success' => true, 'message' => 'Skipped schedule generation until asset purchase details are complete.'];
            }

            $postedSchedules = AssetDepreciationSchedule::where('product_id', $asset->id)
                ->posted()
                ->orderBy('period_no')
                ->get();

            $rows = static::buildSchedule([
                'cost' => $cost,
                'duration' => $duration,
                'duration_period' => $asset->duration_period,
                'depreciation_method' => $asset->depreciation_method,
                'declining_factor' => $asset->declining_factor,
                'start_date' => $asset->prorata_date ?: $asset->purchase_date,
                'posted_count' => $postedSchedules->count(),
                'opening_book_value' => $postedSchedules->last()?->closing_book_value,
                'accumulated_depreciation' => $postedSchedules->last()?->accumulated_depreciation,
            ]);

            $totalPeriods = static::resolveTotalPeriods($duration);

            DB::transaction(function () use ($asset, $userId, $totalPeriods, $rows): void {
                $this->deleteUnpostedSchedules($asset->id, $userId, $totalPeriods);

                foreach ($rows as $row) {
                    AssetDepreciationSchedule::updateOrCreate(
                        [
                            'product_id' => $asset->id,
                            'period_no' => $row['period_no'],
                        ],
                        [
                            'tenant_id' => $asset->tenant_id,
                            'branch_id' => session('branch_id'),
                            'period_type' => $row['period_type'],
                            'schedule_date' => $row['schedule_date'],
                            'opening_book_value' => $row['opening_book_value'],
                            'depreciation_amount' => $row['depreciation_amount'],
                            'accumulated_depreciation' => $row['accumulated_depreciation'],
                            'closing_book_value' => $row['closing_book_value'],
                            'status' => 'pending',
                            'updated_by' => $userId,
                            'created_by' => $userId,
                        ]
                    );
                }
            });

            return ['success' => true, 'message' => 'Depreciation schedule generated successfully.'];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    protected function deleteUnpostedSchedules(int $assetId, ?int $userId, int $totalPeriods): void
    {
        $schedules = AssetDepreciationSchedule::where('product_id', $assetId)
            ->where('status', '!=', 'posted')
            ->where('period_no', '>', $totalPeriods)
            ->get();

        $journalIds = $schedules->pluck('journal_id')->filter()->unique()->values();

        foreach ($schedules as $schedule) {
            $schedule->forceDelete();
        }

        if ($journalIds->isNotEmpty()) {
            $journalDeleteAction = app(JournalDeleteAction::class);
            foreach ($journalIds as $journalId) {
                $journalDeleteAction->execute($journalId, $userId);
            }
        }
    }

    public static function buildSchedule(array $params): array
    {
        $cost = (float) ($params['cost'] ?? 0);
        $duration = (float) ($params['duration'] ?? 0);
        $startDateRaw = $params['start_date'] ?? null;

        if ($cost <= 0 || $duration <= 0 || empty($startDateRaw)) {
            return [];
        }

        try {
            $startDate = Carbon::parse($startDateRaw)->startOfDay();
        } catch (\Throwable $e) {
            return [];
        }

        $period = $params['duration_period'] ?? 'years';
        $method = $params['depreciation_method'] ?? 'straight_line';
        $factor = (float) ($params['declining_factor'] ?? 2.0) ?: 2.0;
        $totalPeriods = static::resolveTotalPeriods($duration);
        $periodType = static::resolvePeriodType($period);

        $postedCount = (int) ($params['posted_count'] ?? 0);
        $opening = (float) ($params['opening_book_value'] ?? $cost);
        $accumulated = (float) ($params['accumulated_depreciation'] ?? 0);

        $rows = [];
        for ($periodNo = $postedCount + 1; $periodNo <= $totalPeriods; $periodNo++) {
            $remaining = round(max($cost - $accumulated, 0), 2);
            if ($remaining <= 0) {
                $amount = 0;
            } elseif ($periodNo === $totalPeriods) {
                $amount = $remaining;
            } elseif ($method === 'declining_balance') {
                $rate = $factor / max($totalPeriods, 1);
                $amount = round(min($remaining, max($opening * $rate, 0)), 2);
            } else {
                $remainingPeriods = max($totalPeriods - $periodNo + 1, 1);
                $amount = round($remaining / $remainingPeriods, 2);
            }

            $closing = round(max($opening - $amount, 0), 2);
            $accumulated = round(min($cost, $accumulated + $amount), 2);

            $rows[] = [
                'period_no' => $periodNo,
                'period_type' => $periodType,
                'schedule_date' => static::resolveScheduleDate($startDate, $periodNo, $period)->toDateString(),
                'opening_book_value' => round($opening, 2),
                'depreciation_amount' => round($amount, 2),
                'accumulated_depreciation' => $accumulated,
                'closing_book_value' => $closing,
            ];

            $opening = $closing;
        }

        return $rows;
    }

    protected static function resolveTotalPeriods(float $duration): int
    {
        return max((int) ceil($duration), 1);
    }

    protected static function resolvePeriodType(?string $durationPeriod): string
    {
        return match ($durationPeriod) {
            'days' => 'daily',
            'years' => 'yearly',
            default => 'monthly',
        };
    }

    protected static function resolveScheduleDate(Carbon $startDate, int $periodNo, ?string $durationPeriod): Carbon
    {
        $offset = $periodNo - 1;

        return match ($durationPeriod) {
            'days' => $startDate->copy()->addDays($offset),
            'months' => $startDate->copy()->addMonthsNoOverflow($offset),
            default => $startDate->copy()->addYearsNoOverflow($offset),
        };
    }
}
