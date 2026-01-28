<?php

namespace App\Livewire\Tailoring;

use App\Actions\Tailoring\Order\GetTailoringOrderAction;
use Livewire\Component;

class View extends Component
{
    public $order_id;

    public $order;

    public $activeCategoryTab;

    public function mount($order_id)
    {
        $this->order_id = $order_id;
        $this->loadOrder();

        if ($this->order && $this->order->items->count() > 0) {
            $this->activeCategoryTab = $this->order->items->first()->tailoring_category_id ?? 'other';
        }
    }

    public function loadOrder()
    {
        $action = new GetTailoringOrderAction();
        $result = $action->execute($this->order_id);

        if ($result['success']) {
            $this->order = $result['data'];
        } else {
            return redirect()->route('tailoring::order::index')->with('error', $result['message']);
        }
    }

    public function setActiveTab($categoryId)
    {
        $this->activeCategoryTab = $categoryId;
    }

    public function getCategoryTabsProperty()
    {
        if (! $this->order || ! $this->order->items) {
            return [];
        }

        $categories = [];
        foreach ($this->order->items as $item) {
            $catId = $item->tailoring_category_id ?: 'other';
            $catName = $item->category->name ?? 'Other';

            if (! isset($categories[$catId])) {
                $categories[$catId] = [
                    'id' => $catId,
                    'name' => $catName,
                    'count' => 0,
                ];
            }
            $categories[$catId]['count']++;
        }

        return array_values($categories);
    }

    public function getItemsByCategory($categoryId)
    {
        if (! $this->order || ! $this->order->items) {
            return collect([]);
        }

        return $this->order->items->filter(function ($item) use ($categoryId) {
            return ($item->tailoring_category_id ?: 'other') == $categoryId;
        });
    }

    public function getGroupedItemsProperty()
    {
        if (! $this->order || ! $this->order->items) {
            return [];
        }

        return $this->order->items->groupBy(function ($item) {
            return $item->tailoring_category_id ?: 'other';
        })->map(function ($items, $catId) {
            return [
                'category' => $items->first()->category,
                'items' => $items,
                'measurements' => $items->first(),
            ];
        });
    }

    public function render()
    {
        return view('livewire.tailoring.view');
    }
}
