<?php

namespace App\Livewire\Tailoring;

use App\Actions\Tailoring\Order\GetTailoringOrderAction;
use Livewire\Component;

class View extends Component
{
    protected $listeners = [
        'tailoring-measurement-updated' => 'loadOrder',
    ];

    public $order_id;

    public $order;

    public $activeCategoryTab;

    public function mount($order_id)
    {
        $this->order_id = $order_id;
        $this->loadOrder();

        if ($this->order && $this->order->items->count() > 0) {
            $firstCatId = $this->order->items->first()->tailoring_category_id ?? 'other';
            $this->activeCategoryTab = (string) $firstCatId;
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
        $this->activeCategoryTab = (string) $categoryId;
    }

    public function openMeasurementModal($itemId): void
    {
        $this->dispatch('open-tailoring-measurement-modal', itemId: (int) $itemId);
    }

    public function getCategoryTabsProperty()
    {
        if (! $this->order || ! $this->order->items) {
            return [];
        }

        $categories = [];
        foreach ($this->order->items as $item) {
            $catId = (string) ($item->tailoring_category_id ?? 'other');
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

        $categoryId = (string) $categoryId;

        return $this->order->items->filter(function ($item) use ($categoryId) {
            $itemCatId = (string) ($item->tailoring_category_id ?? 'other');

            return $itemCatId === $categoryId;
        });
    }

    /**
     * For the active category: split measurement fields into common (same value for all items)
     * and separate (different per item). Returns reference item, common map, separate map.
     */
    public function getMeasurementsCommonAndSeparate($categoryId)
    {
        $items = $this->getItemsByCategory($categoryId);
        if ($items->isEmpty()) {
            return [
                'referenceItem' => null,
                'activeMeasurements' => collect(),
                'common' => [],
                'separate' => [],
                'items' => $items,
            ];
        }

        $referenceItem = $items->first();
        $activeMeasurements = $referenceItem->category?->activeMeasurements ?? collect();
        $sectionGroups = [
            'dimensions' => 'basic_body',
            'components' => 'collar_cuff',
            'styles' => 'specifications',
        ];

        $common = [];
        $separate = [];

        foreach ($sectionGroups as $sectionId) {
            $fields = $activeMeasurements->where('section', $sectionId)->sortBy('sort_order');
            foreach ($fields as $m) {
                $key = $m->field_key;
                $values = $items->map(function ($item) use ($key) {
                    $v = $item->$key ?? null;

                    return $v === '' ? null : $v;
                })->unique()->values();

                if ($values->count() <= 1) {
                    $common[$key] = [
                        'label' => $m->label,
                        'value' => $values->first(),
                        'section' => $sectionId,
                    ];
                } else {
                    $perItem = $items->mapWithKeys(function ($item) use ($key) {
                        $v = $item->$key ?? null;

                        return [$item->item_no => $v === '' ? null : $v];
                    })->all();
                    $separate[$key] = [
                        'label' => $m->label,
                        'section' => $sectionId,
                        'per_item' => $perItem,
                    ];
                }
            }
        }

        return [
            'referenceItem' => $referenceItem,
            'activeMeasurements' => $activeMeasurements,
            'common' => $common,
            'separate' => $separate,
            'items' => $items,
        ];
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
        // Re-merge measurements onto items on every render so data is present after tab switch
        // (runtime attributes from appendMeasurementsToItems are not preserved when Livewire rehydrates)
        if ($this->order) {
            $this->order->loadMissing([
                'items' => fn ($q) => $q->with([
                    'category' => fn ($q) => $q->with('activeMeasurements'),
                    'tailorAssignments.tailor:id,name',
                    'latestTailorAssignment.tailor:id,name',
                ]),
                'measurements.category.activeMeasurements',
            ]);
            $this->order->appendMeasurementsToItems();
        }

        return view('livewire.tailoring.view');
    }
}
