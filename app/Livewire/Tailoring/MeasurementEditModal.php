<?php

namespace App\Livewire\Tailoring;

use App\Models\TailoringCategoryModel;
use App\Models\TailoringCategoryModelType;
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

    public $measurementCopyOptions = [];

    public $measurementCopySourceItemId = '';

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
        $resolvedModelName = $item->categoryModel?->name;
        if (! $resolvedModelName && $item->tailoring_category_model_id) {
            $resolvedModelName = TailoringCategoryModel::query()
                ->whereKey($item->tailoring_category_model_id)
                ->value('name');
        }

        $resolvedModelTypeName = $item->categoryModelType?->name;
        if (! $resolvedModelTypeName && $item->tailoring_category_model_type_id) {
            $resolvedModelTypeName = TailoringCategoryModelType::query()
                ->whereKey($item->tailoring_category_model_type_id)
                ->value('name');
        }

        $this->measurementModalMeta = [
            'category_id' => $item->tailoring_category_id,
            'category_name' => $item->category?->name ?? 'Category',
            'model_id' => $item->tailoring_category_model_id,
            'model_name' => $resolvedModelName ?? 'Standard',
            'model_type_id' => $item->tailoring_category_model_type_id,
            'model_type_name' => $resolvedModelTypeName,
        ];

        $this->measurementModalSections = [];
        $this->measurementModalForm = [];
        $this->measurementModalOptions = [];
        $this->measurementCopyOptions = [];
        $this->measurementCopySourceItemId = '';

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

        $fieldKeys = collect($this->measurementModalForm)->keys()->values()->all();
        $sourceItems = TailoringOrderItem::query()
            ->with(['categoryModel', 'categoryModelType'])
            ->where('tailoring_order_id', $this->orderId)
            ->where('tailoring_category_id', $item->tailoring_category_id)
            ->where('id', '!=', $item->id)
            ->orderBy('item_no')
            ->get();

        $modelNameMap = TailoringCategoryModel::query()
            ->whereIn('id', $sourceItems->pluck('tailoring_category_model_id')->filter()->unique()->values())
            ->pluck('name', 'id');

        $modelTypeNameMap = TailoringCategoryModelType::query()
            ->whereIn('id', $sourceItems->pluck('tailoring_category_model_type_id')->filter()->unique()->values())
            ->pluck('name', 'id');

        $this->measurementCopyOptions = $sourceItems->map(function ($sourceItem) use ($fieldKeys, $modelNameMap, $modelTypeNameMap) {
            $sourceMeasurementRow = TailoringOrderMeasurement::query()
                ->where('tailoring_order_id', $this->orderId)
                ->where('tailoring_category_id', $sourceItem->tailoring_category_id)
                ->where('tailoring_category_model_id', $sourceItem->tailoring_category_model_id)
                ->when(
                    $sourceItem->tailoring_category_model_type_id,
                    fn ($q) => $q->where('tailoring_category_model_type_id', $sourceItem->tailoring_category_model_type_id),
                    fn ($q) => $q->whereNull('tailoring_category_model_type_id')
                )
                ->latest('id')
                ->first();
            $sourceSavedData = is_array($sourceMeasurementRow?->data) ? $sourceMeasurementRow->data : [];

            $preview = [];
            foreach ($fieldKeys as $fieldKey) {
                $itemValue = $sourceItem->{$fieldKey} ?? null;
                $value = ($itemValue !== null && $itemValue !== '') ? $itemValue : ($sourceSavedData[$fieldKey] ?? null);
                if ($value !== null && $value !== '') {
                    $preview[] = (string) $value;
                    if (count($preview) >= 3) {
                        break;
                    }
                }
            }

            $modelName = $sourceItem->categoryModel?->name
                ?? ($modelNameMap[$sourceItem->tailoring_category_model_id] ?? null)
                ?? ($sourceSavedData['tailoring_category_model_name'] ?? null)
                ?? 'Model';
            $modelTypeName = $sourceItem->categoryModelType?->name
                ?? ($modelTypeNameMap[$sourceItem->tailoring_category_model_type_id] ?? null)
                ?? ($sourceSavedData['tailoring_category_model_type_name'] ?? null)
                ?? '-';

            return [
                'id' => (int) $sourceItem->id,
                'label' => 'Item #'.$sourceItem->item_no.' - '.($sourceItem->product_name ?? 'Item'),
                'model' => $modelName,
                'model_type' => $modelTypeName,
                'preview' => implode(' | ', $preview),
            ];
        })->values()->all();

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

    public function applyMeasurementsFromSource(): void
    {
        if (! $this->measurementModalItemId) {
            $this->dispatch('error', ['message' => 'No item selected']);

            return;
        }

        $sourceItemId = (int) $this->measurementCopySourceItemId;
        if (! $sourceItemId) {
            $this->dispatch('error', ['message' => 'Please select a source item']);

            return;
        }

        $sourceItem = TailoringOrderItem::query()
            ->where('tailoring_order_id', $this->orderId)
            ->where('id', $sourceItemId)
            ->first();

        if (! $sourceItem) {
            $this->dispatch('error', ['message' => 'Source item not found']);

            return;
        }

        $sourceMeasurementRow = TailoringOrderMeasurement::query()
            ->where('tailoring_order_id', $this->orderId)
            ->where('tailoring_category_id', $sourceItem->tailoring_category_id)
            ->where('tailoring_category_model_id', $sourceItem->tailoring_category_model_id)
            ->when(
                $sourceItem->tailoring_category_model_type_id,
                fn ($q) => $q->where('tailoring_category_model_type_id', $sourceItem->tailoring_category_model_type_id),
                fn ($q) => $q->whereNull('tailoring_category_model_type_id')
            )
            ->latest('id')
            ->first();
        $sourceSavedData = is_array($sourceMeasurementRow?->data) ? $sourceMeasurementRow->data : [];

        $lockedFields = [
            'tailoring_category_model_id',
            'tailoring_category_model_name',
            'tailoring_category_model_type_id',
            'tailoring_category_model_type_name',
        ];

        foreach (array_keys($this->measurementModalForm) as $fieldKey) {
            if (in_array((string) $fieldKey, $lockedFields, true)) {
                continue;
            }
            $itemValue = $sourceItem->{$fieldKey} ?? null;
            $value = ($itemValue !== null && $itemValue !== '') ? $itemValue : ($sourceSavedData[$fieldKey] ?? '');
            $this->measurementModalForm[$fieldKey] = is_scalar($value) ? (string) $value : '';
        }

        $this->measurementModalNotes = (string) ($sourceItem->tailoring_notes ?? ($sourceMeasurementRow?->tailoring_notes ?? ''));
        $this->dispatch('success', ['message' => 'Measurements copied from selected item']);
    }

    public function render()
    {
        return view('livewire.tailoring.measurement-edit-modal');
    }
}
