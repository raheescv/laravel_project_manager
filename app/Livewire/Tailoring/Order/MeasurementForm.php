<?php

namespace App\Livewire\Tailoring\Order;

use App\Models\TailoringCategory;
use App\Models\TailoringMeasurementOption;
use Livewire\Component;

class MeasurementForm extends Component
{
    public $categoryId;

    public $category;

    public $measurements = [];

    public $form = [];

    public $options = [];

    protected $listeners = [
        'updateCategory' => 'setCategory',
        'setMeasurementData' => 'setMeasurementData',
    ];

    public function mount($categoryId = null, $measurementData = [])
    {
        if ($categoryId) {
            $this->setCategory($categoryId);
        }
        if (! empty($measurementData)) {
            $this->form = $measurementData;
        }
    }

    public function setCategory($id)
    {
        if (is_array($id)) {
            $id = $id['id'] ?? $id['categoryId'] ?? reset($id);
        }
        $this->categoryId = $id;
        $this->category = TailoringCategory::find($this->categoryId);
        if ($this->category) {
            $this->measurements = $this->category->activeMeasurements;
            $this->loadOptions();
            $this->initializeForm();
        } else {
            $this->measurements = [];
        }
    }

    public function setMeasurementData($measurementData)
    {
        $this->form = $measurementData;
    }

    public function loadOptions()
    {
        $this->options = [];
        foreach ($this->measurements as $m) {
            if ($m->field_type == 'select' && $m->options_source) {
                if ($m->options_source == 'category_models') {
                    $this->options[$m->options_source] = $this->category->activeModels()->pluck('name', 'id')->toArray();
                } else {
                    $this->options[$m->options_source] = TailoringMeasurementOption::getOptionsByType($m->options_source);
                }
            }
        }
    }

    public function initializeForm()
    {
        foreach ($this->measurements as $m) {
            if (! isset($this->form[$m->field_key])) {
                $this->form[$m->field_key] = '';
            }
        }
    }

    public function updatedForm($value, $key)
    {
        $this->dispatch('measurementUpdated', ['field' => $key, 'value' => $value]);
    }

    public function render()
    {
        $measurements = collect($this->measurements);
        $sections = [
            'basic_body' => [
                'label' => 'BASIC & BODY',
                'icon' => 'fa fa-info-circle',
                'color' => 'info',
                'fields' => $measurements->where('section', 'basic_body'),
            ],
            'collar_cuff' => [
                'label' => 'COLLAR & CUFF',
                'icon' => 'fa fa-tag',
                'color' => 'success',
                'fields' => $measurements->where('section', 'collar_cuff'),
            ],
            'specifications' => [
                'label' => 'SPECIFICATIONS',
                'icon' => 'fa fa-file-text-o',
                'color' => 'warning',
                'fields' => $measurements->where('section', 'specifications'),
            ],
        ];

        return view('livewire.tailoring.order.measurement-form', [
            'sections' => $sections,
        ]);
    }
}
