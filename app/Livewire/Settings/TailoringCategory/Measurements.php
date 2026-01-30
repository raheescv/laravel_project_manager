<?php

namespace App\Livewire\Settings\TailoringCategory;

use App\Models\TailoringCategory;
use App\Models\TailoringCategoryMeasurement;
use App\Models\TailoringMeasurementOption;
use Livewire\Component;

class Measurements extends Component
{
    public $categoryId;

    public $category;

    public $measurements = [];

    public $showForm = false;

    public $editingMeasurement = null;

    public $field_key;

    public $label;

    public $field_type = 'input';

    public $options_source;

    public $section = 'basic_body';

    public $sort_order = 0;

    public $is_active = true;

    public $is_required = false;

    protected $listeners = [
        'SelectCategoryForMeasurements' => 'setCategory',
        'RefreshTailoringCategoryMeasurementTable' => 'loadMeasurements',
    ];

    public function setCategory($categoryId = null)
    {
        $this->categoryId = $categoryId;
        $this->category = TailoringCategory::find($this->categoryId);
        $this->loadMeasurements();
        $this->showForm = false;
    }

    public function loadMeasurements()
    {
        if ($this->category) {
            $this->measurements = $this->category->measurements()->ordered()->get();
        } else {
            $this->measurements = [];
        }
    }

    public function edit($id)
    {
        $m = TailoringCategoryMeasurement::find($id);
        $this->editingMeasurement = $m->id;
        $this->field_key = $m->field_key;
        $this->label = $m->label;
        $this->field_type = $m->field_type;
        $this->options_source = $m->options_source;
        $this->section = $m->section;
        $this->sort_order = $m->sort_order;
        $this->is_active = $m->is_active;
        $this->is_required = $m->is_required;
        $this->showForm = true;
    }

    public function addNew()
    {
        if (! $this->categoryId) {
            $this->dispatch('error', ['message' => 'Please select a category first.']);

            return;
        }
        $this->resetForm();
        $this->showForm = true;
    }

    public function resetForm()
    {
        $this->editingMeasurement = null;
        $this->field_key = '';
        $this->label = '';
        $this->field_type = 'input';
        $this->options_source = '';
        $this->section = 'basic_body';
        $this->sort_order = (count($this->measurements) > 0 ? collect($this->measurements)->max('sort_order') : 0) + 10;
        $this->is_active = true;
        $this->is_required = false;
    }

    public function save()
    {
        $this->validate([
            'field_key' => 'required',
            'label' => 'required',
            'field_type' => 'required',
            'section' => 'required',
        ]);

        $data = [
            'tenant_id' => $this->category->tenant_id,
            'tailoring_category_id' => $this->categoryId,
            'field_key' => $this->field_key,
            'label' => $this->label,
            'field_type' => $this->field_type,
            'options_source' => $this->options_source,
            'section' => $this->section,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'is_required' => $this->is_required,
        ];

        if ($this->editingMeasurement) {
            TailoringCategoryMeasurement::find($this->editingMeasurement)->update($data);
            $this->dispatch('success', ['message' => 'Measurement updated successfully']);
        } else {
            TailoringCategoryMeasurement::create($data);
            $this->dispatch('success', ['message' => 'Measurement added successfully']);
        }

        $this->loadMeasurements();
        $this->showForm = false;
    }

    public function delete($id)
    {
        TailoringCategoryMeasurement::find($id)->delete();
        $this->loadMeasurements();
        $this->dispatch('success', ['message' => 'Measurement deleted successfully']);
    }

    public function cancel()
    {
        $this->showForm = false;
    }

    public function render()
    {
        return view('livewire.settings.tailoring-category.measurements');
    }
}
