<?php

namespace App\Services\Dashboard\Sources;

use App\Models\AssetDepreciationSchedule;
use App\Models\Product;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Fixed-asset depreciation status and assets per group.
 * Ported from the Livewire `dashboard.asset-overview` component.
 */
class AssetSource extends DashboardSource
{
    public function widgets(): array
    {
        return [
            new WidgetDefinition('asset-kpis', 'Inventory & assets', 'Assets', 'Depreciation status', 'briefcase', 'kpi', 6, 4, ['asset.dashboard overview']),
            new WidgetDefinition('asset-groups', 'Inventory & assets', 'Assets by group', 'Top 6 groups', 'tags', 'meter', 12, 4, ['asset.dashboard overview']),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        $assets = Product::with('mainCategory')->asset()->get();

        return ['blocks' => [$key === 'asset-groups' ? $this->groups($assets) : $this->status($assets)]];
    }

    /**
     * @param  Collection<int, Product>  $assets
     * @return array<string, mixed>
     */
    protected function status(Collection $assets): array
    {
        $accumulated = AssetDepreciationSchedule::posted()
            ->whereIn('product_id', $assets->modelKeys())
            ->selectRaw('product_id, MAX(accumulated_depreciation) as accumulated')
            ->groupBy('product_id')
            ->pluck('accumulated', 'product_id');

        $fullyDepreciated = $assets->filter(fn (Product $asset): bool => $asset->cost > 0 && (float) ($accumulated[$asset->id] ?? 0) >= (float) $asset->cost)->count();
        $disposedThisMonth = $assets->filter(fn (Product $asset): bool => $asset->disposed_at && now()->isSameMonth(Carbon::parse($asset->disposed_at)))->count();
        $due = AssetDepreciationSchedule::pending()->whereDate('schedule_date', '<=', now()->toDateString())->count();

        return Blocks::kpis([
            Blocks::kpi('Total assets', $assets->count(), 'briefcase'),
            Blocks::kpi('Fully depreciated', $fullyDepreciated, 'check-circle', 'ok'),
            Blocks::kpi('Due for depreciation', $due, 'clock-o', 'warn'),
            Blocks::kpi('Disposed', $disposedThisMonth, 'archive', 'bad', sub: 'This month'),
        ]);
    }

    /**
     * @param  Collection<int, Product>  $assets
     * @return array<string, mixed>
     */
    protected function groups(Collection $assets): array
    {
        $groups = $assets
            ->groupBy(fn (Product $asset): string => $asset->mainCategory?->name ?: 'Uncategorized')
            ->map(fn (Collection $group): int => $group->count())
            ->sortDesc()
            ->take(6);
        $max = max(1, (int) $groups->max());

        return Blocks::meters(
            $groups->map(fn (int $count, string $name): array => ['label' => $name, 'value' => $count, 'format' => 'number', 'percent' => $this->percentage($count, $max), 'tone' => 'acc'])->values()->all(),
            'No assets yet.',
        );
    }
}
