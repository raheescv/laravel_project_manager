<?php

namespace App\Livewire\Settings\TailoringCategoryModel;

use App\Actions\Settings\TailoringCategoryModel\CreateAction;
use App\Actions\Settings\TailoringCategoryModel\UpdateAction;
use App\Models\TailoringCategory;
use App\Models\TailoringCategoryModel;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'TailoringCategoryModel-Page-Create-Component' => 'create',
        'TailoringCategoryModel-Page-Update-Component' => 'edit',
    ];

    public $models;

    public $table_id;

    public function create($payload = null)
    {
        $this->mount();
        $categoryId = null;
        if (is_array($payload)) {
            $categoryId = $payload['tailoring_category_id'] ?? $payload['id'] ?? null;
        } elseif ($payload !== null) {
            $categoryId = $payload;
        }
        $this->models = [
            'tailoring_category_id' => $categoryId ?? '',
            'name' => '',
            'description' => '',
            'is_active' => true,
        ];
        $this->dispatch('ToggleTailoringCategoryModelModal');
    }

    public function edit($id)
    {
        $tableId = is_array($id) ? ($id['id'] ?? $id) : $id;
        $this->mount($tableId);
        $this->dispatch('ToggleTailoringCategoryModelModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $this->models = [
                'tailoring_category_id' => '',
                'name' => '',
                'description' => '',
                'is_active' => true,
            ];
        } else {
            $model = TailoringCategoryModel::find($this->table_id);
            $this->models = $model->toArray();
        }
    }

    protected function rules()
    {
        $categoryId = $this->models['tailoring_category_id'] ?? null;

        return [
            'models.tailoring_category_id' => ['required', 'exists:tailoring_categories,id'],
            'models.name' => [
                'required',
                'max:255',
                Rule::unique('tailoring_category_models', 'name')
                    ->where('tenant_id', TailoringCategoryModel::getCurrentTenantId())
                    ->where('tailoring_category_id', $categoryId)
                    ->ignore($this->table_id),
            ],
            'models.description' => ['nullable', 'string', 'max:500'],
            'models.is_active' => ['nullable', 'boolean'],
        ];
    }

    protected $messages = [
        'models.tailoring_category_id.required' => 'Please select a category.',
        'models.name.required' => 'The name field is required',
        'models.name.unique' => 'This name is already used for the selected category.',
        'models.name.max' => 'The name must not be greater than 255 characters.',
        'models.description.max' => 'The description must not be greater than 500 characters.',
    ];

    public function save($close = false)
    {
        $this->validate();
        try {
            if (! $this->table_id) {
                $response = (new CreateAction())->execute($this->models);
            } else {
                $response = (new UpdateAction())->execute($this->models, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleTailoringCategoryModelModal');
            } else {
                $this->mount();
            }
            $this->dispatch('RefreshTailoringCategoryModelTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.tailoring-category-model.page', [
            'categories' => TailoringCategory::ordered()->get(['id', 'name']),
        ]);
    }
}
