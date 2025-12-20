<?php

namespace App\Livewire\Settings\Category;

use App\Actions\Settings\Category\CreateAction;
use App\Actions\Settings\Category\UpdateAction;
use App\Models\Category;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'Category-Page-Create-Component' => 'create',
        'Category-Page-Update-Component' => 'edit',
    ];

    public $categories;

    public $parents;

    public $table_id;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleCategoryModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleCategoryModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            if (! app()->isProduction()) {
                $name = $faker->name;
            }
            $this->categories = [
                'parent_id' => null,
                'name' => $name,
                'sale_visibility_flag' => true,
                'online_visibility_flag' => true,
            ];
        } else {
            $category = Category::with('parent')->find($this->table_id);
            $this->categories = $category->toArray();
            // Ensure visibility flags have default values if null
            if (! isset($this->categories['sale_visibility_flag'])) {
                $this->categories['sale_visibility_flag'] = true;
            }
            if (! isset($this->categories['online_visibility_flag'])) {
                $this->categories['online_visibility_flag'] = true;
            }
        }
        $this->dispatch('SelectDropDownValues');
    }

    protected function rules()
    {
        return [
            'categories.name' => ['required', 'unique:categories,name,'.$this->table_id],
        ];
    }

    protected $messages = [
        'categories.name.required' => 'The name field is required',
        'categories.name.unique' => 'The name is already Registered',
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
            $this->categories['id'] = $response['data']['id'];
            $parent_id = $response['data']['parent_id'];
            $parent_name = $response['data']->parent?->name;
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->table_id);
            if (! $close) {
                $this->dispatch('ToggleCategoryModal');
            } else {
                $this->mount();
            }
            $this->categories['parent_id'] = $parent_id;
            $this->categories['parent']['name'] = $parent_name;
            $this->dispatch('RefreshCategoryTable');
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.category.page');
    }
}
