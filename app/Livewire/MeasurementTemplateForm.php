<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\MeasurementCategory;
use App\Models\MeasurementTemplate;

class MeasurementTemplateForm extends Component
{
    use WithPagination;

    public $category_id;       
    public $template_name;
    public $template_id = null; // For update

    public $limit = 10;
    public $search = '';
    public $sortField = 'id';
    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount($category_id)
    {
        $this->category_id = $category_id;
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedLimit() { $this->resetPage(); }

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

    public function save()
    {
        $this->validate([
            'template_name' => 'required|string|max:255',
        ]);

        if ($this->template_id) {
            // Update existing template
            $template = MeasurementTemplate::find($this->template_id);
            if ($template) {
                $template->update([
                    'name' => $this->template_name,
                ]);
                session()->flash('success', 'Template updated successfully.');
            }
        } else {
            // Create new template
            MeasurementTemplate::create([
                'category_id' => $this->category_id,
                'name' => $this->template_name,
            ]);
            session()->flash('success', 'Template added successfully.');
        }

        $this->reset(['template_name', 'template_id']);
        $this->resetPage();
    }

    public function editTemplate($id)
    {
        $template = MeasurementTemplate::find($id);
        if ($template) {
            $this->template_id = $template->id;
            $this->template_name = $template->name;
        }
    }

    public function deleteTemplate($id)
    {
        if ($template = MeasurementTemplate::find($id)) {
            $template->delete();
            session()->flash('success', 'Template deleted.');
        }
    }

    public function render()
    {
        $templates = MeasurementTemplate::where('category_id', $this->category_id)
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', '%' . trim($this->search) . '%')
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $category = MeasurementCategory::find($this->category_id);

        return view('livewire.measurement-template-form', compact('templates', 'category'));
    }
}
