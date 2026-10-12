<?php

namespace App\Services\Dashboard\Sources;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Dashboard\Blocks;
use App\Services\Dashboard\DashboardSource;
use App\Services\Dashboard\WidgetDefinition;

/**
 * Weekly sales / purchase / expense / income, and the inventory snapshot.
 * Ported from the Livewire `dashboard.top-card` component.
 */
class SalesSummarySource extends DashboardSource
{
    public function widgets(): array
    {
        return [
            new WidgetDefinition('business-kpis', 'Sales', 'Weekly summary', 'Last 7 days · vs the previous 7', 'line-chart', 'kpi', 12, 6, ['sale.dashboard weekly summary']),
            new WidgetDefinition('inventory', 'Inventory & assets', 'Inventory', 'Stock value & catalogue', 'cubes', 'kpi', 6, 4, ['inventory.dashboard status']),
        ];
    }

    public function data(string $key, ?string $option, array $params = []): array
    {
        return $key === 'inventory' ? $this->inventory() : $this->weekly();
    }

    /**
     * @return array{blocks: list<array<string, mixed>>, link: array{label: string, url: string}|null}
     */
    protected function weekly(): array
    {
        $previousWeek = [now()->subDays(14), now()->subDays(7)];

        $sale = (float) Sale::currentBranch()->last7Days()->sum('grand_total');
        $lastSale = (float) Sale::currentBranch()->whereBetween('date', $previousWeek)->sum('grand_total');
        $purchase = (float) Purchase::currentBranch()->last7Days()->sum('grand_total');
        $lastPurchase = (float) Purchase::currentBranch()->whereBetween('date', $previousWeek)->sum('grand_total');
        $expense = (float) JournalEntry::expenseList([])->currentBranch()->last7Days()->sum('debit');
        $lastExpense = (float) JournalEntry::expenseList([])->currentBranch()->whereBetween('date', $previousWeek)->sum('debit');
        $income = (float) JournalEntry::incomeList([])->currentBranch()->last7Days()->sum('credit');
        $lastIncome = (float) JournalEntry::incomeList([])->currentBranch()->whereBetween('date', $previousWeek)->sum('credit');

        return [
            'blocks' => [Blocks::kpis([
                Blocks::kpi('Weekly sales', $sale, 'shopping-cart', 'acc', 'money', delta: $this->delta($sale, $lastSale)),
                Blocks::kpi('Weekly purchase', $purchase, 'truck', 'c2', 'money', delta: $this->delta($purchase, $lastPurchase)),
                Blocks::kpi('Weekly expense', $expense, 'money', 'c4', 'money', delta: $this->delta($expense, $lastExpense, invert: true)),
                Blocks::kpi('Weekly income', $income, 'bank', 'c3', 'money', delta: $this->delta($income, $lastIncome)),
            ])],
            'link' => $this->link('Open sales', 'sale::index', 'sale.view'),
        ];
    }

    /**
     * @return array{blocks: list<array<string, mixed>>, link: array{label: string, url: string}|null}
     */
    protected function inventory(): array
    {
        $stockValue = (float) Inventory::currentBranch()
            ->whereHas('product', fn ($query) => $query->where('products.type', 'product'))
            ->sum('total');

        return [
            'blocks' => [
                Blocks::stat('Total stock value', $stockValue),
                Blocks::kpis([
                    Blocks::kpi('Categories', Category::whereNull('parent_id')->count(), 'tags', 'c4'),
                    Blocks::kpi('Products', Product::product()->count(), 'cube', 'ok'),
                    Blocks::kpi('Services', Product::service()->count(), 'wrench', 'c3'),
                ]),
            ],
            'link' => $this->link('Open inventory', 'inventory::index', 'inventory.view'),
        ];
    }

    /**
     * @return array{value: float, invert: bool, hint: string}
     */
    protected function delta(float $current, float $previous, bool $invert = false): array
    {
        $change = $previous === 0.0 ? 0.0 : round((($current - $previous) / $previous) * 100, 1);

        return ['value' => $change, 'invert' => $invert, 'hint' => 'vs the previous 7 days'];
    }
}
