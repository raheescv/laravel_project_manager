<?php

namespace App\Livewire\Product;

use App\Exports\Templates\ProductImportTemplate;
use App\Imports\ProductImport;
use App\Jobs\Product\ImportProductJob;
use App\Models\Category;
use App\Models\Department;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class Import extends Component
{
    use WithFileUploads;

    public $file;

    public $step = 1;

    public $headers = [];

    public $mappings = [];

    public $previewData = [];

    public $filePath;

    // Available fields for mapping
    public $availableFields = [
        'name' => 'Product Name English(*)',
        'name_arabic' => 'Product Name Arabic (*)',
        'code' => 'Product Code (SKU)',
        'barcode' => 'Barcode',
        'cost' => 'Buying Price',
        'mrp' => 'Selling Price',
        'tax' => 'Tax Rate',
        'hsn_code' => 'HSN Code',
        'unit_id' => 'Unit (ID or Name)',
        'department_id' => 'Department (ID or Name)',
        'main_category_id' => 'Main Category (ID or Name)',
        'sub_category_id' => 'Sub Category (ID or Name)',
        'brand_id' => 'Brand (ID or Name)',
        'size' => 'Size',
        'color' => 'Color',
        'model' => 'Model',
        'pattern' => 'Pattern',
        'description' => 'Description',
        'stock' => 'Opening Stock',
        'status' => 'Status (active/inactive)',
        'is_selling' => 'Is Selling (1/0)',
        'is_favorite' => 'Is Favorite (1/0)',
        'part_no' => 'Part No',
        'min_stock' => 'Min Stock',
        'max_stock' => 'Max Stock',
        'location' => 'Location',
        'reorder_level' => 'Reorder Level',
        'plu' => 'PLU',
        'upload_type' => 'Upload Type (new/update)',
    ];

    /**
     * Allowed Excel header variants per internal field (normalized for comparison).
     * Used for accurate mapping so e.g. "name" does not match "arabic_name".
     */
    private function getHeaderAliases(): array
    {
        return [
            'name' => ['name', 'productname', 'productnameenglish', 'product name', 'product name english'],
            'name_arabic' => ['name_arabic', 'namearabic', 'arabicname', 'productnamearabic', 'product name arabic', 'arabic name', 'name arabic'],
            'code' => ['code', 'sku', 'productcode', 'product code'],
            'barcode' => ['barcode'],
            'cost' => ['cost', 'buyingprice', 'buying price', 'purchaseprice'],
            'mrp' => ['mrp', 'price', 'sellingprice', 'selling price'],
            'tax' => ['tax', 'taxrate', 'tax rate'],
            'hsn_code' => ['hsn_code', 'hsncode', 'hsn code'],
            'unit_id' => ['unit_id', 'unitid', 'unit'],
            'department_id' => ['department_id', 'departmentid', 'department'],
            'main_category_id' => ['main_category_id', 'maincategoryid', 'main_category', 'maincategory', 'main category'],
            'sub_category_id' => ['sub_category_id', 'subcategoryid', 'sub_category', 'subcategory', 'sub category'],
            'brand_id' => ['brand_id', 'brandid', 'brand'],
            'size' => ['size'],
            'color' => ['color'],
            'model' => ['model'],
            'pattern' => ['pattern'],
            'description' => ['description'],
            'stock' => ['stock', 'openingstock', 'opening stock', 'quantity'],
            'is_selling' => ['is_selling', 'isselling', 'is selling'],
            'is_favorite' => ['is_favorite', 'isfavorite', 'is favorite'],
            'part_no' => ['part_no', 'partno', 'part no'],
            'min_stock' => ['min_stock', 'minstock', 'min stock'],
            'max_stock' => ['max_stock', 'maxstock', 'max stock'],
            'location' => ['location'],
            'reorder_level' => ['reorder_level', 'reorderlevel', 'reorder level'],
            'plu' => ['plu'],
            'upload_type' => ['upload_type', 'uploadtype', 'upload type'],
            'status' => ['status', 'active', 'inactive'],
        ];
    }

    private function normalizeHeader(string $value): string
    {
        return strtolower(str_replace(['_', ' ', '-'], '', $value));
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        $this->filePath = $this->file->store('temp-imports', 'public');

        // Load headers
        $headings = (new HeadingRowImport())->toArray(Storage::disk('public')->path($this->filePath));
        $this->headers = $headings[0][0] ?? [];

        $aliases = $this->getHeaderAliases();

        // Auto-map only when header exactly matches an allowed alias for that field
        foreach ($this->availableFields as $field => $label) {
            $allowed = $aliases[$field] ?? [$this->normalizeHeader($field)];
            $normalizedAllowed = array_map(fn ($a) => $this->normalizeHeader($a), $allowed);
            foreach ($this->headers as $header) {
                $normalizedHeader = $this->normalizeHeader($header);
                if (in_array($normalizedHeader, $normalizedAllowed, true)) {
                    $this->mappings[$field] = $header;
                    break;
                }
            }
        }

        $this->step = 2;
        $this->loadPreview();
    }

    public function loadPreview()
    {
        $rows = Excel::toArray(new \stdClass(), Storage::disk('public')->path($this->filePath));
        $this->previewData = array_slice($rows[0], 1, 10); // Skip header and get 10 rows
    }

    public function goToStep($step)
    {
        $this->step = $step;
    }

    public function sample()
    {
        return Excel::download(new ProductImportTemplate(), 'product_import_template.xlsx');
    }

    public function save()
    {
        $this->validate([
            'mappings.name' => 'required',
        ], [
            'mappings.name.required' => 'The Product Name field must be mapped.',
        ]);

        // In a real "Advanced" import, we'd pass mappings to the job.
        // For now, if the user didn't change anything and used the template,
        // the existing ProductImport might work if it uses HeadingRow.
        // However, to be TRULY advanced, we should probably modify ProductImport
        // or pass the mapping to the job.

        // Since I want to fulfill the "Advanced" request, I'll update the Job/Import class
        // to handle custom mappings if I can, or at least ensure the current one works.

        // For this demo, let's assume we use the mappings.
        $totalRows = count(Excel::toArray(new \stdClass(), Storage::disk('public')->path($this->filePath))[0]) - 1;

        ImportProductJob::dispatch(Auth::id(), $this->filePath, session('branch_id'), session('tenant_id'), $this->mappings);

        $this->dispatch('success', ['message' => 'Import started in background']);
        $this->step = 4;
    }

    public function render()
    {
        return view('livewire.product.import', [
            'units' => Unit::pluck('name', 'id'),
            'departments' => Department::pluck('name', 'id'),
            'categories' => Category::whereNull('parent_id')->pluck('name', 'id'),
        ]);
    }
}
