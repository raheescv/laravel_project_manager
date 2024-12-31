<?php

namespace App\Livewire\Product;

use App\Actions\Product\CreateAction;
use App\Actions\Product\UpdateAction;
use App\Models\Product;
use App\Models\Unit;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Page extends Component
{
    public $table_id;

    public $selectedTab = 'Attributes';

    public $products;

    public $departments;

    public function mount($table_id = null, $dropdown = true)
    {
        $this->table_id = $table_id;
        $this->departments = [];
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            $code = '';
            if (! app()->isProduction()) {
                $name = $faker->name;
                $code = $faker->hexcolor;
            }
            $this->products = [
                'code' => $code,
                'name' => $name,
                'is_selling' => true,
                'hsn_code' => '',
                'tax' => 5,
                'cost' => rand(100, 900),
                'mrp' => rand(1000, 9000),
                'pattern' => '',
                'color' => '',
                'size' => '',
                'model' => '',
                'brand' => '',
                'part_no' => '',
                'min_stock' => 0,
                'max_stock' => 0,
                'location' => '',
                'reorder_level' => '',
                'plu' => '',
                'unit_id' => 1,
                'main_category_id' => null,
                'sub_category_id' => null,
                'department_id' => null,
                'department' => ['id' => 1, 'name' => 'Food'],
                'subCategory' => [],
                'mainCategory' => [],
            ];
        } else {
            $department = Product::with('department', 'subCategory', 'mainCategory')->find($this->table_id);
            $this->products = $department->toArray();
        }
        if ($dropdown) {
            $this->dispatch('SelectDropDownValues', $this->products);
        }
    }

    protected function rules()
    {
        return [
            'products.name' => ['required', Rule::unique(Product::class, 'name')->whereNull('deleted_at')->ignore($this->table_id)],
            'products.code' => ['required', Rule::unique(Product::class, 'code')->whereNull('deleted_at')->ignore($this->table_id)],
            'products.unit_id' => ['required'],
            'products.department_id' => ['required'],
            'products.main_category_id' => ['required'],
            'products.sub_category_id' => ['required'],
            'products.cost' => ['required'],
            'products.mrp' => ['required'],
        ];
    }

    protected $messages = [
        'products.name.required' => 'The name field is required',
        'products.name.unique' => 'The name is already Registered',
        'products.name.max' => 'The name field must not be greater than 20 characters.',
        'products.code.required' => 'The code field is required',
        'products.code.unique' => 'The code is already Registered',
        'products.code.max' => 'The code field must not be greater than 20 characters.',
        'products.unit_id' => 'The unit field is required.',
        'products.department_id' => 'The department  field is required.',
        'products.main_category_id' => 'The main category field is required.',
        'products.sub_category_id' => 'The sub category field is required.',
        'products.cost' => 'The cost field is required.',
        'products.mrp' => 'The mrp field is required.',
    ];

    public function save()
    {
        $this->validate();
        try {
            DB::beginTransaction();
            if (! $this->table_id) {
                $selected['mainCategory'] = [
                    'id' => $this->products['main_category_id'],
                    'name' => $this->products['main_category_id'],
                ];
                $selected['subCategory'] = [
                    'id' => $this->products['sub_category_id'],
                    'name' => $this->products['sub_category_id'],
                ];
                $selected['department'] = [
                    'id' => $this->products['department_id'],
                    'name' => $this->products['department_id'],
                ];
                $response = (new CreateAction)->execute($this->products);
            } else {
                $response = (new UpdateAction)->execute($this->products, $this->table_id);
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);

            $this->mount($this->table_id, $dropdown = false);

            $this->products['department_id'] = $selected['department']['id'];
            $this->products['main_category_id'] = $selected['mainCategory']['id'];
            $this->products['sub_category_id'] = $selected['subCategory']['id'];

            $this->dispatch('SelectDropDownValues', $this->products);
            $this->dispatch('ResetThePage');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function tabSelect($key)
    {
        $this->selectedTab = $key;
    }

    public function render()
    {
        $units = Unit::pluck('name', 'id')->toArray();

        return view('livewire.product.page', compact('units'));
    }
}
