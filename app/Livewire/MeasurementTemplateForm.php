<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Category;
use App\Models\MeasurementTemplate;

class MeasurementTemplateForm extends Component
{
    use WithPagination;

    public $category_id;
    public $template_name;

    public $search = '';
    public $limit = 10;
    public $sortField = 'id';
    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

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
            'category_id' => 'required|exists:categories,id',
            'template_name' => 'required|string|max:255',
        ]);

        MeasurementTemplate::create([
            'category_id' => $this->category_id,
            'name'        => $this->template_name,
        ]);

        session()->flash('success', 'Template added successfully.');
        $this->reset(['category_id', 'template_name']);
        $this->resetPage();
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
        $templates = MeasurementTemplate::with('category')
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', '%' . trim($this->search) . '%')
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $categories = Category::orderBy('name')->get();

        return view('livewire.measurement-template-form', compact('templates', 'categories'));
    }
}
