<?php

namespace App\Livewire\Settings\MeasurementCategory;

use App\Actions\Settings\MeasurementCategory\CreateAction;
use App\Actions\Settings\MeasurementCategory\UpdateAction;
use App\Models\MeasurementCategory;
use Faker\Factory;
use Livewire\Component;

class Page extends Component
{
    protected $listeners = [
        'MeasurementCategory-Page-Create-Component' => 'create',
        'MeasurementCategory-Page-Update-Component' => 'edit',
    ];

    public $categories;
    public $table_id;

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

        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';

            if (! app()->isProduction()) {
                $name = $faker->word;
            }

            $this->categories = [
                'name' => $name,
            ];
        } else {
            $category = MeasurementCategory::find($this->table_id);
            $this->categories = $category->toArray();
        }
    }

    protected function rules()
    {
        return [
            'categories.name' => ['required', 'unique:measurement_categories,name,' . $this->table_id],
        ];
    }

    protected $messages = [
        'categories.name.required' => 'The name field is required',
        'categories.name.unique' => 'The name is already registered',
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
                throw new \Exception($response['message']);
            }

            $this->categories['id'] = $response['data']['id'];

            $this->dispatch('success', ['message' => $response['message']]);

            if (! $close) {
                $this->dispatch('ToggleMeasurementCategoryModal');
            } else {
                $this->mount();
            }

            $this->dispatch('RefreshMeasurementCategoryTable');

        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.settings.measurement-category.page');
    }
}
