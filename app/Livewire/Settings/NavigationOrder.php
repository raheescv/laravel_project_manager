<?php

namespace App\Livewire\Settings;

use App\Models\Configuration;
use App\Services\NavigationService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

class NavigationOrder extends Component
{
    public array $items = [];

    public function mount(): void
    {
        $this->items = $this->filterItemsByActiveModule(NavigationService::getNavigationItems());
    }

    #[On('nav-order-updated')]
    public function updateOrder(array $ids): void
    {
        $itemsById = collect($this->items)->keyBy('id')->toArray();
        $newItems = [];

        foreach ($ids as $id) {
            if (isset($itemsById[$id])) {
                $newItems[] = $itemsById[$id];
            }
        }

        // Keep any items that weren't in the dragged list
        foreach ($this->items as $item) {
            if (! in_array($item['id'], $ids)) {
                $newItems[] = $item;
            }
        }

        $this->items = $newItems;
    }

    public function toggleVisibility(string $id): void
    {
        foreach ($this->items as &$item) {
            if ($item['id'] === $id) {
                $item['visible'] = ! ($item['visible'] ?? true);
                break;
            }
        }
    }

    public function save(): void
    {
        $savedItems = collect(NavigationService::getNavigationItems())->keyBy('id');
        $orderData = [];
        $usedIds = [];

        foreach ($this->items as $item) {
            $id = $item['id'] ?? null;
            if (! $id) {
                continue;
            }

            $orderData[] = [
                'id' => $id,
                'visible' => $item['visible'] ?? true,
            ];
            $usedIds[$id] = true;
        }

        // Keep non-visible module items in saved order to avoid losing their preferences.
        foreach ($savedItems as $id => $item) {
            if (! isset($usedIds[$id])) {
                $orderData[] = [
                    'id' => $id,
                    'visible' => $item['visible'] ?? true,
                ];
            }
        }

        Configuration::updateOrCreate(
            ['key' => 'nav_order'],
            ['value' => json_encode($orderData)]
        );

        Cache::forget('nav_order');
        $this->dispatch('success', ['message' => 'Navigation order saved successfully']);
    }

    public function resetToDefault(): void
    {
        Configuration::where('key', 'nav_order')->delete();
        Cache::forget('nav_order');
        $this->items = $this->filterItemsByActiveModule(NavigationService::defaultItems());
        $this->dispatch('success', ['message' => 'Navigation reset to default order']);
    }

    public function render()
    {
        return view('livewire.settings.navigation-order');
    }

    private function filterItemsByActiveModule(array $items): array
    {
        $activeModule = Configuration::where('key', 'active_module')->value('value');

        if (! $activeModule) {
            return $items;
        }

        $enabledModuleKeys = config("modules.systems.{$activeModule}", []);
        if (empty($enabledModuleKeys)) {
            return $items;
        }

        $navModuleMap = $this->navItemModuleMap();

        return array_values(array_filter($items, function (array $item) use ($enabledModuleKeys, $navModuleMap): bool {
            $id = $item['id'] ?? null;
            if (! $id) {
                return false;
            }

            $requiredModuleKeys = $navModuleMap[$id] ?? ['core'];

            return ! empty(array_intersect($requiredModuleKeys, $enabledModuleKeys));
        }));
    }

    private function navItemModuleMap(): array
    {
        return [
            'dashboard' => ['core'],
            'inventory' => ['product_management', 'inventory_management'],
            'rent-out' => ['rent_out'],
            'property-sales' => ['lease'],
            'leads' => ['property_management'],
            'maintenance' => ['maintenance'],
            'issue' => ['support'],
            'appointments' => ['saloon'],
            'tailoring' => ['tailoring'],
            'sale' => ['sales'],
            'day-session' => ['sales'],
            'purchase' => ['simple_purchase_management'],
            'package' => ['saloon'],
            'account' => ['accounting'],
            'employee' => ['hr_management'],
            'purchase-workflow' => ['advanced_purchase_management'],
            'asset-supply' => ['maintenance'],
            'users' => ['core'],
            'tenants' => ['property_management'],
            'flat-trade' => ['core'],
            'tickets' => ['core'],
            'log' => ['core'],
        ];
    }
}
