<?php

namespace App\Livewire\Product;

use App\Actions\Asset\GenerateDepreciationScheduleAction;
use App\Actions\Product\CreateAction;
use App\Actions\Product\DeleteImageAction;
use App\Actions\Product\ProductPrice\DeleteAction as PriceDeleteAction;
use App\Actions\Product\ProductUnit\DeleteAction as UnitDeleteAction;
use App\Actions\Product\UpdateAction;
use App\Models\Configuration;
use App\Models\Department;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ProductImageAiService;
use Faker\Factory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

    public bool $syncBarcodeToCode = false;

    public $selectedTab = 'Prices';

    public $product;

    public $products;

    public $images = [];

    public $angles_360 = [];

    public $degree = [];

    public $departments;

    public $brands;

    public $relatedProducts = [];

    public $document_file;

    public $previewImage = null;

    public function refresh()
    {
        $this->mount($this->type, $this->table_id, $dropdownValues = false);
    }

    public function mount($type = 'product', $table_id = null, $dropdownValues = true)
    {
        $this->barcode_type = Configuration::where('key', 'barcode_type')->value('value');
        $this->syncBarcodeToCode = Configuration::where('key', 'sync_barcode_to_code')->value('value') === 'yes';
        $this->table_id = $table_id;
        $this->type = $type;
        $this->selectedTab = $this->type === 'asset' ? 'Attributes' : 'Prices';
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
                'size_category' => '',
                'model' => '',
                'brand_id' => null,
                'part_no' => '',
                'item_no' => '',
                'supplier_name' => '',
                'purchase_date' => null,
                'duration' => null,
                'duration_period' => 'years',
                'depreciation_method' => 'straight_line',
                'declining_factor' => 2.0,
                'depreciation_amount' => 0,
                'prorata_date' => null,
                'min_stock' => 0,
                'max_stock' => 0,
                'location' => '',
                'reorder_level' => '',
                'plu' => '',
                'opening_stock' => null,
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
                'income_account_id' => null,
                'expense_account_id' => null,
                'asset_account_id' => null,
                'accumulated_depreciation_account_id' => null,
                'depreciation_expense_account_id' => null,
                'income_account' => [],
                'expense_account' => [],
                'asset_account' => [],
                'accumulated_depreciation_account' => [],
                'depreciation_expense_account' => [],
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
                'incomeAccount',
                'expenseAccount',
                'assetAccount',
                'accumulatedDepreciationAccount',
                'depreciationExpenseAccount'
            )->find($this->table_id);
            if (! $this->product) {
                return redirect()->route($this->type === 'asset' ? 'asset::index' : ($this->type === 'service' ? 'service::index' : 'product::index'));
            }
            $this->products = $this->product->toArray();
            $this->type = $this->product->type;
            $this->selectedTab = $this->type === 'asset' ? 'Attributes' : 'Prices';
            $this->loadRelatedProducts();
        }
        if ($this->type === 'asset') {
            $this->recalculateDepreciation();
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
            'products.department_id' => ['required'],
            'products.main_category_id' => ['required'],
            'products.cost' => ['required'],
            // 'products.barcode' => ['required_if:products.type,product'],
            'images.*' => 'mimes:jpg,jpeg,png,gif,bmp,webp,svg|max:3100',
            'angles_360.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,bmp,webp,svg|max:10240',
            'degree.*' => 'nullable|integer|min:0|max:359',
            'document_file' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,rar|max:10240',
        ];

        if ($this->type !== 'service') {
            $rules['products.unit_id'] = ['required'];
        }

        if ($this->type !== 'asset') {
            $rules['products.mrp'] = ['required'];
        } else {
            $rules['products.mrp'] = ['nullable'];
            $rules['products.purchase_date'] = ['required', 'date'];
            $rules['products.duration'] = ['required', 'numeric', 'min:0.01'];
            $rules['products.duration_period'] = ['required', 'in:days,months,years'];
            $rules['products.depreciation_method'] = ['required', 'in:straight_line,declining_balance'];
            $rules['products.declining_factor'] = ['nullable', 'numeric', 'min:0.01'];
            $rules['products.prorata_date'] = ['nullable', 'date'];
            $rules['products.asset_account_id'] = ['required'];
            $rules['products.accumulated_depreciation_account_id'] = ['required'];
            $rules['products.depreciation_expense_account_id'] = ['required'];
        }

        if (! $this->table_id) {
            $rules['products.opening_stock'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    protected $messages = [
        'products.name.required' => 'The name field is required',
        'products.name.unique' => 'The name is already Registered',
        'products.name.max' => 'The name field must not be greater than 20 characters.',
        'products.code.required' => 'The code field is required',
        'products.unit_id' => 'The unit field is required.',
        'products.department_id' => 'The department  field is required.',
        'products.main_category_id' => 'The main category field is required.',
        'products.sub_category_id' => 'The sub category field is required.',
        'products.brand_id' => 'The brand field is required.',
        'products.cost' => 'The cost field is required.',
        'products.mrp' => 'The mrp field is required.',
        'products.purchase_date.required' => 'The purchase date field is required for assets.',
        'products.duration.required' => 'The depreciation duration is required for assets.',
        'products.duration_period.required' => 'Please choose the depreciation period for the asset.',
        'products.depreciation_method.required' => 'Please choose a depreciation method for the asset.',
        'products.asset_account_id.required' => 'Please select the asset account.',
        'products.accumulated_depreciation_account_id.required' => 'Please select the accumulated depreciation account.',
        'products.depreciation_expense_account_id.required' => 'Please select the depreciation expense account.',
        // 'products.barcode.required_if' => 'The barcode field is required when type is product.',
        'images.mimetypes' => 'The images field must be a file of type: image.',
        'images.*.max' => 'The images field must not be greater than 3100 KB',
        'angles_360.*.mimes' => 'The 360-degree images must be files of type: jpg, jpeg, png, gif, bmp, webp, svg.',
        'angles_360.*.max' => 'The 360-degree images must not be greater than 10240 KB',
        'degree.*.integer' => 'The angle must be a number.',
        'degree.*.min' => 'The angle must be at least 0 degrees.',
        'degree.*.max' => 'The angle must not be greater than 359 degrees.',
    ];

    /**
     * Resolve the permission group for the current entity type (product/asset/service).
     */
    protected function permissionGroup(): string
    {
        return match ($this->type) {
            'asset' => 'asset',
            'service' => 'service',
            default => 'product',
        };
    }

    public function save($edit = false)
    {
        abort_unless(auth()->user()?->can($this->permissionGroup().'.'.($this->table_id ? 'edit' : 'create')), 403);
        if (
            $this->syncBarcodeToCode &&
            $this->type === 'product' &&
            ! $this->table_id
        ) {
            $barcodeNumber = trim((string) ($this->products['barcode_number'] ?? ''));
            $upcCode = trim((string) ($this->products['code'] ?? ''));

            if ($barcodeNumber !== '') {
                $this->products['code'] = $barcodeNumber;
            } elseif ($upcCode !== '') {
                $this->products['barcode_number'] = $upcCode;
                $this->products['code'] = $upcCode;
            }
        }

        $this->validate();
        try {
            DB::beginTransaction();
            $this->products['images'] = $this->images;
            $this->products['angles_360'] = $this->angles_360;
            $this->products['degree'] = $this->degree;
            $this->products['document_file_upload'] = $this->document_file;
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
                } elseif ($this->type == 'asset') {
                    return redirect()->route('asset::edit', $response['data']['id']);
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
            $this->document_file = null;
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

    public function updatedProductsBarcodeNumber($value): void
    {
        if (
            $this->syncBarcodeToCode &&
            $this->type === 'product' &&
            ! $this->table_id
        ) {
            $this->products['code'] = trim((string) $value);
        }
    }

    public function updatedProducts($value, $key): void
    {
        if (
            $this->type === 'asset' &&
            in_array($key, ['cost', 'duration', 'duration_period', 'depreciation_method', 'declining_factor'], true)
        ) {
            $this->recalculateDepreciation();
        }
    }

    protected function recalculateDepreciation(): void
    {
        if ($this->type !== 'asset') {
            return;
        }

        $cost = (float) ($this->products['cost'] ?? 0);
        $duration = (float) ($this->products['duration'] ?? 0);
        $period = $this->products['duration_period'] ?? 'years';
        $method = $this->products['depreciation_method'] ?? 'straight_line';
        $factor = (float) ($this->products['declining_factor'] ?? 2.0);

        if ($cost <= 0 || $duration <= 0) {
            $this->products['depreciation_amount'] = 0;

            return;
        }

        $totalPeriods = $this->getDepreciationTotalPeriods($duration, $period);
        $amount = 0;

        if ($method === 'declining_balance') {
            $rate = $factor / max($totalPeriods, 1);
            $amount = $cost * $rate;
        } else {
            $amount = $cost / max($totalPeriods, 1);
        }

        $this->products['depreciation_amount'] = round(max($amount, 0), 2);
    }

    protected function getDepreciationTotalPeriods(float $duration, string $period): float
    {
        return max($duration, 1);
    }

    public function getDepreciationPreviewProperty(): array
    {
        $cost = (float) ($this->products['cost'] ?? 0);
        $duration = (float) ($this->products['duration'] ?? 0);
        $period = $this->products['duration_period'] ?? 'years';
        $method = $this->products['depreciation_method'] ?? 'straight_line';
        $factor = (float) ($this->products['declining_factor'] ?? 2.0);
        $amount = (float) ($this->products['depreciation_amount'] ?? 0);

        $periodLabel = match ($period) {
            'days' => 'day',
            'months' => 'month',
            default => 'year',
        };
        $amountLabel = match ($period) {
            'days' => 'Daily Depreciation',
            'months' => 'Monthly Depreciation',
            default => 'Yearly Depreciation',
        };

        if ($cost <= 0 || $duration <= 0) {
            return [
                'title' => 'Enter purchase cost and duration to calculate depreciation.',
                'formula' => null,
                'detail' => null,
                'amount_label' => $amountLabel,
            ];
        }

        $totalPeriods = $this->getDepreciationTotalPeriods($duration, $period);

        if ($method === 'declining_balance') {
            $rate = $factor / max($totalPeriods, 1);

            return [
                'title' => 'First '.$periodLabel.' depreciation using declining balance.',
                'formula' => currency($cost).' x ('.number_format($factor, 2).' / '.number_format($totalPeriods, 2).') = '.currency($amount),
                'detail' => 'Declining balance changes each period, so this field shows the first '.$periodLabel.' amount.',
                'amount_label' => 'First '.ucfirst($periodLabel).' Depreciation',
            ];
        }

        return [
            'title' => 'Straight line depreciation spread evenly across '.number_format($totalPeriods, 2).' '.$periodLabel.($totalPeriods == 1 ? '' : 's').'.',
            'formula' => currency($cost).' / '.number_format($totalPeriods, 2).' = '.currency($amount),
            'detail' => 'This gives a constant '.strtolower($amountLabel).' amount for the full asset life.',
            'amount_label' => $amountLabel,
        ];
    }

    public function getDepreciationTimelineProperty(): array
    {
        return GenerateDepreciationScheduleAction::buildSchedule([
            'cost' => $this->products['cost'] ?? 0,
            'duration' => $this->products['duration'] ?? 0,
            'duration_period' => $this->products['duration_period'] ?? 'years',
            'depreciation_method' => $this->products['depreciation_method'] ?? 'straight_line',
            'declining_factor' => $this->products['declining_factor'] ?? 2.0,
            'start_date' => $this->products['prorata_date'] ?? ($this->products['purchase_date'] ?? null),
        ]);
    }

    public function deleteImage($id)
    {
        // TODO(C7): sub-record (product image) delete during edit; no exact sub-permission, gated by edit
        abort_unless(auth()->user()?->can($this->permissionGroup().'.edit'), 403);
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
        // TODO(C7): mutates product thumbnail during edit; no exact sub-permission, gated by edit
        abort_unless(auth()->user()?->can($this->permissionGroup().'.edit'), 403);
        try {
            $this->product->update(['thumbnail' => $path]);
            $this->products['thumbnail'] = $path;
            $this->dispatch('success', ['message' => 'Thumbnail Updated Successfully']);
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function setPreview($path)
    {
        $this->previewImage = $path;
    }

    public function closePreview()
    {
        $this->previewImage = null;
    }

    public function unitDelete($id)
    {
        // TODO(C7): sub-record (product unit) delete during edit; no exact sub-permission, gated by edit
        abort_unless(auth()->user()?->can($this->permissionGroup().'.edit'), 403);
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
        // TODO(C7): sub-record (product price) delete during edit; no exact sub-permission, gated by edit
        abort_unless(auth()->user()?->can($this->permissionGroup().'.edit'), 403);
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

    public function removeDocument()
    {
        // TODO(C7): sub-record (product document) delete during edit; no exact sub-permission, gated by edit
        abort_unless(auth()->user()?->can($this->permissionGroup().'.edit'), 403);
        try {
            if ($this->product && $this->product->document_file) {
                $relativePath = str_replace('/storage/', '', parse_url($this->product->document_file, PHP_URL_PATH));
                Storage::disk('public')->delete($relativePath);
                $this->product->update(['document_file' => null, 'document_file_name' => null]);
                $this->products['document_file'] = null;
                $this->products['document_file_name'] = null;
                $this->dispatch('success', ['message' => 'Document removed successfully']);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function downloadImageWithAi()
    {
        abort_unless(auth()->user()?->can('product.ai image generation'), 403);
        if (! $this->table_id) {
            $this->dispatch('error', ['message' => 'Please save the product before downloading an AI image.']);

            return;
        }

        try {
            /** @var Product|null $product */
            $product = Product::find($this->table_id);

            if (! $product) {
                throw new \Exception('Product not found.');
            }

            $response = app(ProductImageAiService::class)->generateAndAttach($product, null, true);

            if (! ($response['success'] ?? false)) {
                throw new \Exception($response['message'] ?? 'Failed to generate product image.');
            }

            $this->mount($this->type, $this->table_id, $dropdownValues = false);
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $units = Unit::pluck('name', 'id')->toArray();

        return view('livewire.product.page', compact('units'));
    }
}
