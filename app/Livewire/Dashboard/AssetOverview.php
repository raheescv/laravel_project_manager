<?php

namespace App\Livewire\Dashboard;

use App\Models\AssetDepreciationSchedule;
use App\Models\Product;
use Carbon\Carbon;
use Livewire\Component;

class AssetOverview extends Component
{
    public function render()
    {
        $assets = Product::with('mainCategory')
            ->asset()
            ->get();

        $totalAssets = $assets->count();
        $dueSchedules = AssetDepreciationSchedule::pending()
            ->whereDate('schedule_date', '<=', now()->toDateString())
            ->count();
        $disposedThisMonth = $assets->filter(fn ($asset) => $asset->disposed_at && now()->isSameMonth(Carbon::parse($asset->disposed_at)))->count();
        $fullyDepreciated = $assets->filter(function ($asset) {
            $accumulated = (float) $asset->depreciationSchedules()->posted()->latest('period_no')->value('accumulated_depreciation');

            return $asset->cost > 0 && $accumulated >= (float) $asset->cost;
        })->count();

        $assetsByGroup = $assets
            ->groupBy(fn ($asset) => $asset->mainCategory?->name ?: 'Uncategorized')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->take(6);

        return view('livewire.dashboard.asset-overview', compact(
            'totalAssets',
            'dueSchedules',
            'disposedThisMonth',
            'fullyDepreciated',
            'assetsByGroup',
        ));
    }
}
