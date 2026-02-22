<?php

namespace App\Livewire\Tailoring;

use App\Models\TailoringMeasurementOption;
use App\Models\TailoringOrderItem;
use App\Models\TailoringOrderMeasurement;
use Livewire\Component;

class MeasurementEditModal extends Component
{
    protected $listeners = [
        'open-tailoring-measurement-modal' => 'openMeasurementModal',
    ];

    public $orderId;

    public $measurementModalItemId = null;

    public $measurementModalItemTitle = '';

    public $measurementModalMeta = [];

    public $measurementModalSections = [];

    public $measurementModalForm = [];

    public $measurementModalOptions = [];

    public $measurementModalNotes = '';

    public function mount($orderId): void
    {
        $this->orderId = (int) $orderId;
    }

    public function openMeasurementModal($itemId): void
    {
        $item = TailoringOrderItem::query()
            ->with([
                'category.activeMeasurements',
                'categoryModel',
                'categoryModelType',
            ])
            ->where('tailoring_order_id', $this->orderId)
            ->find((int) $itemId);

        if (! $item) {
            $this->dispatch('error', ['message' => 'Item not found']);

            return;
        }

        $activeMeasurements = $item->category?->activeMeasurements?->sortBy('sort_order') ?? collect();
        if ($activeMeasurements->isEmpty()) {
            $this->dispatch('error', ['message' => 'No active measurements configured for this category']);

            return;
        }

        $this->measurementModalItemId = $item->id;
        $this->measurementModalItemTitle = 'Item #'.$item->item_no.' - '.($item->product_name ?? 'Product');
        $this->measurementModalMeta = [
            'category_id' => $item->tailoring_category_id,
            'category_name' => $item->category?->name ?? 'Category',
            'model_id' => $item->tailoring_category_model_id,
            'model_name' => $item->categoryModel?->name ?? 'Standard',
            'model_type_id' => $item->tailoring_category_model_type_id,
            'model_type_name' => $item->categoryModelType?->name,
        ];

        $this->measurementModalSections = [];
        $this->measurementModalForm = [];
        $this->measurementModalOptions = [];

        $measurementRow = TailoringOrderMeasurement::query()
            ->where('tailoring_order_id', $this->orderId)
            ->where('tailoring_category_id', $item->tailoring_category_id)
            ->where('tailoring_category_model_id', $item->tailoring_category_model_id)
            ->when(
                $item->tailoring_category_model_type_id,
                fn ($q) => $q->where('tailoring_category_model_type_id', $item->tailoring_category_model_type_id),
                fn ($q) => $q->whereNull('tailoring_category_model_type_id')
            )
            ->latest('id')
            ->first();
        $savedData = is_array($measurementRow?->data) ? $measurementRow->data : [];

        foreach ($activeMeasurements->values() as $index => $measurement) {
            $fieldKey = (string) $measurement->field_key;
            $section = (string) ($measurement->section ?: 'specifications');
            $fieldType = (string) ($measurement->field_type ?: 'text');
            $optionsSource = (string) ($measurement->options_source ?: '');

            $this->measurementModalSections[$section][] = [
                'field_key' => $fieldKey,
                'label' => $measurement->label,
                'field_type' => $fieldType,
                'options_source' => $optionsSource,
                'is_required' => (bool) ($measurement->is_required ?? false),
            ];

            $currentValue = $item->{$fieldKey}
                ?? ($savedData[$fieldKey] ?? ($savedData[(string) $index] ?? ($savedData[$index] ?? '')));

            if ($fieldType === 'select' && $optionsSource !== '') {
                if ($optionsSource === 'category_models') {
                    $this->measurementModalOptions[$fieldKey] = $item->category?->activeModels()->pluck('name', 'id')->toArray() ?? [];
                } else {
                    $this->measurementModalOptions[$fieldKey] = TailoringMeasurementOption::getOptionsByType($optionsSource);
                }

                // Store/select by option value (label), not option id.
                // Backward compatibility: if old saved data is an id, convert id -> label for prefill.
                if ($currentValue !== '') {
                    $options = $this->measurementModalOptions[$fieldKey];
                    $currentValueStr = (string) $currentValue;

                    if (array_key_exists($currentValueStr, $options)) {
                        $currentValue = (string) $options[$currentValueStr];
                    } else {
                        $matchedLabel = collect($options)->first(function ($label) use ($currentValueStr) {
                            return mb_strtolower((string) $label) === mb_strtolower($currentValueStr);
                        });

                        if ($matchedLabel !== null) {
                            $currentValue = (string) $matchedLabel;
                        }
                    }
                }
            }

            $this->measurementModalForm[$fieldKey] = is_scalar($currentValue) ? (string) $currentValue : '';
        }

        $this->measurementModalNotes = (string) ($item->tailoring_notes ?? ($measurementRow?->tailoring_notes ?? ''));

        $this->dispatch('tailoring-measurement-modal-open');
    }

    public function saveMeasurementModal(): void
    {
        if (! $this->measurementModalItemId) {
            $this->dispatch('error', ['message' => 'No item selected']);

            return;
        }

        $categoryId = $this->measurementModalMeta['category_id'] ?? null;
        $modelId = $this->measurementModalMeta['model_id'] ?? null;
        if (! $categoryId || ! $modelId) {
            $this->dispatch('error', ['message' => 'Category or model is missing for this item']);

            return;
        }

        $rules = [];
        foreach (array_keys($this->measurementModalForm) as $fieldKey) {
            $rules["measurementModalForm.$fieldKey"] = ['nullable', 'string', 'max:255'];
        }
        $rules['measurementModalNotes'] = ['nullable', 'string', 'max:1000'];
        $this->validate($rules);

        $measurementPayload = [];
        foreach ($this->measurementModalForm as $fieldKey => $value) {
            $normalized = trim((string) $value);
            if ($normalized !== '') {
                $measurementPayload[(string) $fieldKey] = $normalized;
            }
        }

        TailoringOrderMeasurement::updateOrCreate(
            [
                'tailoring_order_id' => $this->orderId,
                'tailoring_category_id' => $categoryId,
                'tailoring_category_model_id' => $modelId,
                'tailoring_category_model_type_id' => $this->measurementModalMeta['model_type_id'] ?: null,
            ],
            [
                'tailoring_notes' => trim((string) $this->measurementModalNotes) ?: null,
                'data' => $measurementPayload,
                'updated_by' => auth()->id(),
            ]
        );

        $this->dispatch('tailoring-measurement-modal-close');
        $this->dispatch('tailoring-measurement-updated');
        $this->dispatch('success', ['message' => 'Measurements updated successfully']);
    }

    public function render()
    {
        return view('livewire.tailoring.measurement-edit-modal');
    }
}
