<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\MeasurementCategory;
use App\Models\MeasurementTemplate;
use Illuminate\Support\Facades\DB;

class MeasurementTemplateForm extends Component
{
    use WithPagination;

    public $category_id;
    public $template_name;
    public $template_id = null;

    public $limit = 10;
    public $search = '';
    public $sortField = 'id';
    public $sortDirection = 'desc';

    public $showModal = false;

    // MULTI DELETE
    public $selectAll = false;
    public $selectedTemplates = [];

    protected $paginationTheme = 'bootstrap';

    public function mount($category_id)
    {
       $category = MeasurementCategory::findOrFail($category_id);
        $this->category_id = $category->id;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedLimit()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    /* ================= MODAL ================= */

    public function openModal()
    {
        $this->reset(['template_name', 'template_id']);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
    }

    public function editTemplate($id)
    {
        $template = MeasurementTemplate::find($id);
        if ($template) {
            $this->template_id = $template->id;
            $this->template_name = $template->name;
            $this->showModal = true;
        }
    }

    /* ================= SAVE ================= */

public function save($saveNew = false)
{
    $this->validate([
        'template_name' => 'required|string|max:255',
        'category_id' => 'required',
    ]);

    // Temporarily disable FK checks
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    if ($this->template_id) {
        MeasurementTemplate::where('id', $this->template_id)
            ->update(['name' => $this->template_name]);
    } else {
        MeasurementTemplate::create([
            'category_id' => $this->category_id, // can be from your other category table
            'name' => $this->template_name,
        ]);
    }

    // Re-enable FK checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    session()->flash('success', 'Template saved successfully.');

    $saveNew
        ? $this->reset(['template_name', 'template_id'])
        : $this->closeModal();

    $this->resetPage();
}



    /* ================= SINGLE DELETE ================= */

    public function deleteTemplate($id)
    {
        MeasurementTemplate::where('id', $id)->delete();
        session()->flash('success', 'Template deleted.');
    }

    /* ================= MULTI DELETE ================= */

    // Select all ONLY current page
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedTemplates = MeasurementTemplate::where('category_id', $this->category_id)
                ->when($this->search, fn ($q) =>
                    $q->where('name', 'like', '%' . trim($this->search) . '%')
                )
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->limit)
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedTemplates = [];
        }
    }

    // Uncheck select-all if any row unchecked
    public function updatedSelectedTemplates()
    {
        $this->selectAll = false;
    }

    public function bulkDelete()
    {
        if (!count($this->selectedTemplates)) {
            return;
        }

        MeasurementTemplate::whereIn('id', $this->selectedTemplates)->delete();

        $this->reset(['selectedTemplates', 'selectAll']);

        session()->flash('success', 'Selected templates deleted successfully.');
    }

    /* ================= RENDER ================= */

    public function render()
    {
        $templates = MeasurementTemplate::where('category_id', $this->category_id)
            ->when($this->search, fn ($q) =>
                $q->where('name', 'like', '%' . trim($this->search) . '%')
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $category = MeasurementCategory::find($this->category_id);

        return view('livewire.measurement-template-form', compact('templates', 'category'));
    }
}
