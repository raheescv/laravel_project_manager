<?php

namespace App\Livewire\Product;

use App\Actions\Product\CreateAction;
use App\Actions\Product\DeleteImageAction;
use App\Actions\Product\ProductPrice\DeleteAction as PriceDeleteAction;
use App\Actions\Product\ProductUnit\DeleteAction as UnitDeleteAction;
use App\Actions\Product\UpdateAction;
use App\Models\Configuration;
use App\Models\Department;
use App\Models\Product;
use App\Models\Unit;
use Faker\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\LivewireFilepond\WithFilePond;

class Page extends Component
{
    use WithFilePond;

    protected $listeners = [
        'Product-Refresh-Component' => 'refresh',
    ];

    public $type;

    public $table_id;

    public $barcode_type;

    public $selectedTab = 'Prices';

    public $product;

    public $products;

    public $images = [];

    public $angles_360 = [];

    public $degree = [];

    public $departments;

    public $brands;

    public $relatedProducts = [];

    public function refresh()
    {
        $this->mount($this->type, $this->table_id, $dropdownValues = false);
    }

    public function mount($type = 'product', $table_id = null, $dropdownValues = true)
    {
        $this->barcode_type = Configuration::where('key', 'barcode_type')->value('value');
        $this->table_id = $table_id;
        $this->type = $type;
        $this->departments = [];
        $this->brands = [];
        if (! $this->table_id) {
            $faker = Factory::create();
            $name = '';
            $code = time();
            $code = '';
            $barcode = '';
            $mrp = 0;
            $cost = 0;
            if (! app()->isProduction()) {
                $name = $faker->name;
                $code = $faker->hexcolor;
                $barcode = $faker->ean13();
                $code = '';
                $cost = rand(100, 900);
                $mrp = rand(1000, 9000);
            }
            $this->products = [
                'type' => $this->type,
                'code' => $code,
                'name' => $name,
                'barcode' => $barcode,
                'is_selling' => true,
                'is_favorite' => false,
                'hsn_code' => '',
                'tax' => 0,
                'cost' => $cost,
                'mrp' => $mrp,
                'pattern' => '',
                'color' => '',
                'size' => '',
                'model' => '',
                'brand_id' => null,
                'part_no' => '',
                'min_stock' => 0,
                'max_stock' => 0,
                'location' => '',
                'reorder_level' => '',
                'plu' => '',
                'unit_id' => 1,
                'main_category_id' => null,
                'sub_category_id' => null,
                'status' => 'active',
                'department_id' => 1,
                'department' => Department::first(['id', 'name'])->toArray(),
                'sub_category' => [],
                'main_category' => [],
                'images' => [],
                'angles_360' => [],
                'degree' => [],
            ];
        } else {
            $this->product = Product::with('department', 'subCategory', 'mainCategory', 'brand', 'images', 'unit', 'units.subUnit', 'prices')->find($this->table_id);
            if (! $this->product) {
                return redirect()->route('product::index');
            }
            $this->products = $this->product->toArray();
            $this->type = $this->product->type;
            $this->loadRelatedProducts();
        }
        if ($dropdownValues) {
            $this->dispatch('SelectDropDownValues', $this->products);
        }
    }

    public function loadRelatedProducts()
    {
        if ($this->table_id && isset($this->products['code']) && ! empty($this->products['code'])) {
            $this->relatedProducts = Product::where('code', $this->products['code'])
                ->where('id', '!=', $this->table_id)
                ->with(['department', 'mainCategory', 'subCategory', 'brand', 'unit'])
                ->orderBy('name')
                ->get()
                ->toArray();
        } else {
            $this->relatedProducts = [];
        }
    }

    protected function rules()
    {
        $rules = [
            'products.name' => ['required'],
            // 'products.code' => ['required'],
            'products.unit_id' => ['required'],
            'products.department_id' => ['required'],
            'products.main_category_id' => ['required'],
            'products.cost' => ['required'],
            'products.mrp' => ['required'],
            // 'products.barcode' => ['required_if:products.type,product'],
            'images.*' => 'mimes:jpg,jpeg,png,gif,bmp,webp,svg|max:3100',
            'angles_360.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,bmp,webp,svg|max:10240',
            'degree.*' => 'nullable|integer|min:0|max:359',
        ];

        return $rules;
    }

    protected $messages = [
        'products.name.required' => 'The name field is required',
        'products.name.unique' => 'The name is already Registered',
        'products.name.max' => 'The name field must not be greater than 20 characters.',
        // 'products.code.required' => 'The code field is required',
        // 'products.code.max' => 'The code field must not be greater than 20 characters.',
        'products.unit_id' => 'The unit field is required.',
        'products.department_id' => 'The department  field is required.',
        'products.main_category_id' => 'The main category field is required.',
        'products.sub_category_id' => 'The sub category field is required.',
        'products.cost' => 'The cost field is required.',
        'products.mrp' => 'The mrp field is required.',
        // 'products.barcode.required_if' => 'The barcode field is required when type is product.',
        'images.mimetypes' => 'The images field must be a file of type: image.',
        'images.*.max' => 'The images field must not be greater than 3100 KB',
        'angles_360.*.mimes' => 'The 360-degree images must be files of type: jpg, jpeg, png, gif, bmp, webp, svg.',
        'angles_360.*.max' => 'The 360-degree images must not be greater than 10240 KB',
        'degree.*.integer' => 'The angle must be a number.',
        'degree.*.min' => 'The angle must be at least 0 degrees.',
        'degree.*.max' => 'The angle must not be greater than 359 degrees.',
    ];

    public function save($edit = false)
    {
        $this->validate();
        try {
            DB::beginTransaction();
            $this->products['images'] = $this->images;
            $this->products['angles_360'] = $this->angles_360;
            $this->products['degree'] = $this->degree;
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
                $selected['brand'] = [
                    'id' => $this->products['brand_id'],
                    'name' => $this->products['brand_id'],
                ];
                $response = (new CreateAction())->execute($this->products, Auth::id());
            } else {
                $response = (new UpdateAction())->execute($this->products, $this->table_id, Auth::id());
            }
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            DB::commit();
            if ($edit) {
                if ($this->type == 'product') {
                    return redirect()->route('product::edit', $response['data']['id']);
                } else {
                    return redirect()->route('service::edit', $response['data']['id']);
                }
            }
            $this->mount($this->type, $this->table_id, $dropdownValues = false);
            if (! $this->table_id) {
                $this->products['department_id'] = $selected['department']['id'];
                $this->products['main_category_id'] = $selected['mainCategory']['id'];
                $this->products['sub_category_id'] = $selected['subCategory']['id'];
            }
            $this->dispatch('SelectDropDownValues', $this->products);
            $this->dispatch('filepond-reset-images');
            if ($this->table_id) {
                $this->loadRelatedProducts();
            }
        } catch (\Throwable $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function tabSelect($key)
    {
        $this->selectedTab = $key;
        if ($key == 'Related' && $this->table_id) {
            $this->loadRelatedProducts();
        }
    }

    public function deleteImage($id)
    {
        try {
            DB::beginTransaction();
            $response = (new DeleteImageAction())->execute($id);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->mount($this->type, $this->table_id);
            $this->dispatch('success', ['message' => 'Deleted Successfully']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function defaultImage($path)
    {
        try {
            $this->product->update(['thumbnail' => $path]);
            $this->dispatch('success', ['message' => 'Thumbnail Updated Successfully']);
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function unitDelete($id)
    {
        try {
            $response = (new UnitDeleteAction())->execute($id);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->type, $this->table_id);
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function priceDelete($id)
    {
        try {
            $response = (new PriceDeleteAction())->execute($id);
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            $this->dispatch('success', ['message' => $response['message']]);
            $this->mount($this->type, $this->table_id);
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $units = Unit::pluck('name', 'id')->toArray();

        return view('livewire.product.page', compact('units'));
    }
}
