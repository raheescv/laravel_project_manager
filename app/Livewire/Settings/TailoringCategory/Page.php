<?php

namespace App\Livewire\Settings\TailoringCategory;

use App\Actions\Settings\TailoringCategory\CreateAction;
use App\Actions\Settings\TailoringCategory\UpdateAction;
use App\Models\TailoringCategory;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'TailoringCategory-Page-Create-Component' => 'create',
        'TailoringCategory-Page-Update-Component' => 'edit',
    ];

    public $categories;

    public $table_id;

    public function create($name = null)
    {
        $this->mount();
        $this->categories = [
            'name' => $name,
            'description' => '',
            'is_active' => true,
            'order' => TailoringCategory::max('order') + 1,
        ];
        $this->dispatch('ToggleTailoringCategoryModal');
    }

    public function edit($id)
    {
        $tableId = is_array($id) ? ($id['id'] ?? $id) : $id;
        $this->mount($tableId);
        $this->dispatch('ToggleTailoringCategoryModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $this->categories = [
                'name' => '',
                'description' => '',
                'is_active' => true,
                'order' => 0,
            ];
        } else {
            $category = TailoringCategory::find($this->table_id);
            $this->categories = $category->toArray();
        }
    }

    protected function rules()
    {
        return [
            'categories.name' => ['required', 'max:255', Rule::unique('tailoring_categories', 'name')->where('tenant_id', TailoringCategory::getCurrentTenantId())->ignore($this->table_id)],
            'categories.description' => ['nullable', 'string', 'max:500'],
            'categories.is_active' => ['nullable', 'boolean'],
            'categories.order' => ['nullable', 'integer'],
        ];
    }

    protected $messages = [
        'categories.name.required' => 'The name field is required',
        'categories.name.unique' => 'The name is already registered',
        'categories.name.max' => 'The name field must not be greater than 255 characters.',
        'categories.description.max' => 'The description must not be greater than 500 characters.',
        'categories.order.integer' => 'The order field must be an integer.',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->categories);
            } else {
                $response = (new UpdateAction())->execute($this->categories, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleTailoringCategoryModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshTailoringCategoryTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.tailoring-category.page');
    }
}
