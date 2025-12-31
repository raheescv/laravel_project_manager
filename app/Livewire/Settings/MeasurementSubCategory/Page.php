<?php

namespace App\Livewire\Settings\MeasurementSubCategory;

use Livewire\Component;
use Faker\Factory;
use App\Models\MeasurementCategory;
use App\Models\MeasurementSubCategory;
use App\Actions\Settings\MeasurementSubCategory\CreateAction;
use App\Actions\Settings\MeasurementSubCategory\UpdateAction;

class Page extends Component
{
    protected $listeners = [
        'MeasurementCategory-Page-Create-Component' => 'create',
        'MeasurementCategory-Page-Update-Component' => 'edit',
    ];

    public $allCategories = [];
    public $categories = [];
    public $table_id = null;

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleMeasurementCategoryModal');
    }

    public function edit($id)
    {
        $this->mount($id);
        $this->dispatch('ToggleMeasurementCategoryModal');
    }

    public function mount($table_id = null)
    {
        $this->table_id = $table_id;
        $this->allCategories = MeasurementCategory::orderBy('name')->get();

        if (!$table_id) {
            $faker = Factory::create();

            $this->categories = [
                'name' => app()->isProduction() ? '' : $faker->word,
                'measurement_category_id' => null,
            ];
        } else {
            $subcategory = MeasurementSubCategory::findOrFail($table_id);

            $this->categories = [
                'name' => $subcategory->name,
                'measurement_category_id' => $subcategory->measurement_category_id,
            ];
        }
    }

    protected function rules()
    {
        return [
            'categories.name' => 'required|unique:measurement_sub_categories,name,' . $this->table_id,
            'categories.measurement_category_id' => 'required|exists:measurement_categories,id',
        ];
    }

    protected $messages = [
        'categories.name.required' => 'The name field is required',
        'categories.name.unique' => 'This subcategory already exists',
        'categories.measurement_category_id.required' => 'Please select a category',
    ];

    public function save($close = false)
    {
        $this->validate();

        try {
            if ($this->table_id) {
                $response = (new UpdateAction())->execute(
                    $this->categories,
                    $this->table_id
                );
            } else {
                $response = (new CreateAction())->execute($this->categories);
            }

            if (!$response['success']) {
                throw new \Exception($response['message']);
            }

            $this->dispatch('success', ['message' => $response['message']]);
            $this->dispatch('RefreshMeasurementCategoryTable');

            if (!$close) {
                $this->dispatch('ToggleMeasurementCategoryModal');
            } else {
                $this->mount();
            }
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.measurement-sub-category.page');
    }
}
