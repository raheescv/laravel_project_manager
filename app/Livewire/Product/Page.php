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
use App\Models\ProductRawMaterial;
use App\Models\Unit;
use App\Models\MeasurementCategory;
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

    /** 🔥 RAW MATERIALS (NEW – SAFE) */
    public $raw_materials = [];


    public $departments;
    public $brands;

    public $relatedProducts = [];

    public function refresh()
    {
        $this->mount($this->type, $this->table_id, false);
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
            $this->products = [
                'type' => $this->type,
                'code' => '',
                'name' => '',
                'barcode' => null,
                'is_selling' => true,
                'is_favorite' => false,
                'hsn_code' => '',
                'tax' => 0,
                'cost' => 0,
                'mrp' => 0,
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

            /** 🔥 DEFAULT ONE ROW */
            $this->raw_materials = [
                ['name' => '', 'quantity' => 1, 'price' => 0]
            ];

        } else {

            $this->product = Product::with(
                'department',
                'subCategory',
                'mainCategory',
                'brand',
                'images',
                'unit',
                'units.subUnit',
                'prices',
                'rawMaterials'
            )->find($this->table_id);

            if (! $this->product) {
                return redirect()->route('product::index');
            }

            $this->products = $this->product->toArray();
            $this->type = $this->product->type;

            /** 🔥 LOAD RAW MATERIALS FOR EDIT */
            $this->raw_materials = $this->product->rawMaterials
                ->map(fn ($rm) => [
                    'name' => $rm->name,
                    'quantity' => '1',
                    'price' => $rm->price,
                ])->toArray();
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
        return [
            'products.name' => ['required'],
            'products.code' => ['required'],
            'products.unit_id' => ['required'],
            'products.department_id' => ['required'],
            'products.main_category_id' => ['required'],
            'products.cost' => ['required'],
            'products.mrp' => ['required'],
            'products.barcode' => ['required_if:products.type,product'],
        ];
    }

    /** 🔥 RAW MATERIAL METHODS (ONLY ADDITION) */
    public function addRawMaterial()
    {
        $this->raw_materials[] = [
            'name' => '',
          
            'price' => 0,
        ];
    }

    public function removeRawMaterial($index)
    {
        unset($this->raw_materials[$index]);
        $this->raw_materials = array_values($this->raw_materials);
    }

    public function save($edit = false)
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $this->products['images'] = $this->images;
            $this->products['angles_360'] = $this->angles_360;
            $this->products['degree'] = $this->degree;

            $response = $this->table_id
                ? (new UpdateAction())->execute($this->products, $this->table_id, Auth::id())
                : (new CreateAction())->execute($this->products, Auth::id());

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            $productId = $this->table_id ?? $response['data']['id'];

            /** 🔥 SYNC RAW MATERIALS */
            ProductRawMaterial::where('product_id', $productId)->delete();

            foreach ($this->raw_materials as $rm) {
                if (!trim($rm['name'])) continue;

                ProductRawMaterial::create([
                    'product_id' => $productId,
                    'name' => $rm['name'],
                    
                    'price' => $rm['price'],
                ]);
            }

            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);

        } catch (\Throwable $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    /** 🔥 NOTHING CHANGED BELOW */
    public function tabSelect($key) { $this->selectedTab = $key; }
    public function deleteImage($id) {}
    public function defaultImage($path) {}
    public function unitDelete($id) {}
    public function priceDelete($id) {}

    public function render()
    {
        

    $measurementCategories = MeasurementCategory::pluck('name','id')->toArray();

        $units = Unit::pluck('name', 'id')->toArray();
       $allProducts = Product::where('status', 'active')
        ->pluck('name', 'id')
        ->toArray();

    return view('livewire.product.page', compact(
        'units',
        'measurementCategories',
        'allProducts'
    ));

    
    }
}
