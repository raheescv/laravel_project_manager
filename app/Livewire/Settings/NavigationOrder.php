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
        $this->items = NavigationService::getNavigationItems();
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
        // Use the full unfiltered list so items belonging to other modules
        // retain their saved visibility/order preferences across module switches.
        $savedItems = collect(NavigationService::getOrderedItems())->keyBy('id');
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
        $this->items = NavigationService::filterByActiveModule(NavigationService::defaultItems());
        $this->dispatch('success', ['message' => 'Navigation reset to default order']);
    }

    public function render()
    {
        return view('livewire.settings.navigation-order');
    }
}
