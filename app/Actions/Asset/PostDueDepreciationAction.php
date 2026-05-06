<?php

namespace App\Actions\Asset;

use App\Models\AssetDepreciationSchedule;
use Carbon\Carbon;

class PostDueDepreciationAction
{
    public function execute(int $userId, ?string $asOfDate = null, ?int $branchId = null, ?int $assetId = null): array
    {
        $asOfDate = Carbon::parse($asOfDate ?: now())->toDateString();

        $query = AssetDepreciationSchedule::with('product')
            ->whereIn('status', ['pending', 'failed', 'locked'])
            ->whereDate('schedule_date', '<=', $asOfDate)
            ->when($branchId, fn ($builder) => $builder->where('branch_id', $branchId))
            ->when($assetId, fn ($builder) => $builder->where('product_id', $assetId))
            ->orderBy('schedule_date')
            ->orderBy('id');

        $posted = 0;
        $failed = [];
        foreach ($query->get() as $schedule) {
            $response = (new PostDepreciationAction())->execute($schedule, $userId);
            if ($response['success']) {
                $posted++;
            } else {
                $failed[] = [
                    'asset' => $schedule->product?->name,
                    'schedule_date' => optional($schedule->schedule_date)->toDateString(),
                    'message' => $response['message'],
                ];
            }
        }

        return [
            'success' => true,
            'message' => "Posted {$posted} depreciation schedule(s).",
            'data' => ['posted' => $posted, 'failed' => $failed],
        ];
    }
}
