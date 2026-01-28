<?php

namespace App\Livewire\Service;

use App\Exports\Templates\ServiceImportTemplate;
use App\Jobs\Product\ImportServiceJob;
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
        'name' => 'Service Name English(*)',
        'name_arabic' => 'Service Name Arabic (*)',
        'code' => 'Service Code (SKU)',
        'cost' => 'Price',
        'price' => 'Price (alternative)',
        'department_id' => 'Department (ID or Name)',
        'main_category_id' => 'Main Category (ID or Name)',
        'sub_category_id' => 'Sub Category (ID or Name)',
        'hsn_code' => 'HSN Code',
        'description' => 'Description',
        'is_favorite' => 'Is Favorite (1/0)',
        'home_service' => 'Home Service Price',
        'time' => 'Time (for home service)',
        'status' => 'Status (active/inactive)',
    ];

    /**
     * Allowed Excel header variants per internal field (normalized for comparison).
     * Used for accurate mapping so e.g. "name" does not match "arabic_name".
     */
    private function getHeaderAliases(): array
    {
        return [
            'name' => ['name', 'servicename', 'servicenameenglish', 'service name', 'service name english'],
            'name_arabic' => ['name_arabic', 'namearabic', 'arabicname', 'servicenamearabic', 'service name arabic', 'arabic name', 'name arabic'],
            'code' => ['code', 'sku', 'servicecode', 'service code'],
            'cost' => ['cost', 'price', 'buyingprice', 'buying price'],
            'price' => ['price', 'cost', 'sellingprice', 'selling price'],
            'department_id' => ['department_id', 'departmentid', 'department'],
            'main_category_id' => ['main_category_id', 'maincategoryid', 'main_category', 'maincategory', 'main category'],
            'sub_category_id' => ['sub_category_id', 'subcategoryid', 'sub_category', 'subcategory', 'sub category'],
            'hsn_code' => ['hsn_code', 'hsncode', 'hsn code'],
            'description' => ['description'],
            'is_favorite' => ['is_favorite', 'isfavorite', 'is favorite'],
            'home_service' => ['home_service', 'homeservice', 'home service'],
            'time' => ['time'],
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
        return Excel::download(new ServiceImportTemplate(), 'service_import_template.xlsx');
    }

    public function save()
    {
        $this->validate([
            'mappings.name' => 'required',
        ], [
            'mappings.name.required' => 'The Service Name field must be mapped.',
        ]);

        $totalRows = count(Excel::toArray(new \stdClass(), Storage::disk('public')->path($this->filePath))[0]) - 1;

        ImportServiceJob::dispatch(Auth::id(), $this->filePath, session('branch_id'), session('tenant_id'), $this->mappings);

        $this->dispatch('success', ['message' => 'Import started in background']);
        $this->step = 4;
    }

    public function render()
    {
        return view('livewire.service.import', [
            'units' => Unit::pluck('name', 'id'),
            'departments' => Department::pluck('name', 'id'),
            'categories' => Category::whereNull('parent_id')->pluck('name', 'id'),
        ]);
    }
}
