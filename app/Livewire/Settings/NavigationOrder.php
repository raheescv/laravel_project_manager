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
        $orderData = array_map(fn ($item) => [
            'id' => $item['id'],
            'visible' => $item['visible'] ?? true,
        ], $this->items);

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
        $this->items = NavigationService::defaultItems();
        $this->dispatch('success', ['message' => 'Navigation reset to default order']);
    }

    public function render()
    {
        return view('livewire.settings.navigation-order');
    }
}
