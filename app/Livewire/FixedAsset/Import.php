<?php

namespace App\Livewire\FixedAsset;

use App\Exports\ErrorsExport;
use App\Exports\Templates\FixedAssetImportTemplate;
use App\Jobs\Product\ImportProductJob;
use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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

    public array $previewErrors = [];

    public array $fieldMeta = [];

    public string $entityLabel = 'Asset';

    public $availableFields = [
        'name' => 'Asset Name (*)',
        'name_arabic' => 'Arabic Name',
        'code' => 'Asset Code',
        'unit' => 'Unit (ID or Name)',
        'department' => 'Department (ID or Name)',
        'main_category' => 'Asset Group (ID or Name)',
        'sub_category' => 'Sub Category (ID or Name)',
        'brand' => 'Brand (ID or Name)',
        'item_no' => 'Item No',
        'color' => 'Color',
        'supplier_name' => 'Supplier Name',
        'location' => 'Location',
        'purchase_date' => 'Purchase Date',
        'cost' => 'Purchase Cost',
        'mrp' => 'Selling Price',
        'duration' => 'Duration',
        'duration_period' => 'Duration Period',
        'depreciation_method' => 'Depreciation Method',
        'declining_factor' => 'Declining Factor',
        'prorata_date' => 'Prorata Date',
        'description' => 'Description',
        'status' => 'Status',
        'upload_type' => 'Upload Type (new/update)',
    ];

    public function mount(): void
    {
        $this->buildFieldMeta();
    }

    private function getHeaderAliases(): array
    {
        return [
            'name' => ['name', 'assetname', 'fixedassetname', 'asset name'],
            'name_arabic' => ['name_arabic', 'namearabic', 'arabicname', 'assetnamearabic', 'arabic name'],
            'code' => ['code', 'assetcode', 'fixedassetcode', 'sku'],
            'unit' => ['unit', 'unitid'],
            'department' => ['department', 'departmentid'],
            'main_category' => ['main_category', 'maincategory', 'main category', 'assetgroup', 'asset group'],
            'sub_category' => ['sub_category', 'subcategory', 'sub category'],
            'brand' => ['brand', 'brandid'],
            'item_no' => ['item_no', 'itemno', 'item no'],
            'color' => ['color'],
            'supplier_name' => ['supplier_name', 'suppliername', 'supplier name'],
            'location' => ['location'],
            'purchase_date' => ['purchase_date', 'purchasedate', 'purchase date'],
            'cost' => ['cost', 'purchasecost', 'purchase cost', 'buyingprice'],
            'mrp' => ['mrp', 'sellingprice', 'selling price', 'price'],
            'duration' => ['duration'],
            'duration_period' => ['duration_period', 'durationperiod', 'duration period'],
            'depreciation_method' => ['depreciation_method', 'depreciationmethod', 'depreciation method'],
            'declining_factor' => ['declining_factor', 'decliningfactor', 'declining factor'],
            'prorata_date' => ['prorata_date', 'proratadate', 'prorata date'],
            'description' => ['description', 'description', 'remark', 'remarks', 'note', 'notes'],
            'status' => ['status'],
            'upload_type' => ['upload_type', 'uploadtype', 'upload type'],
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
        $headings = (new HeadingRowImport())->toArray(Storage::disk('public')->path($this->filePath));
        $this->headers = $headings[0][0] ?? [];

        $aliases = $this->getHeaderAliases();

        foreach ($this->availableFields as $field => $label) {
            $allowed = $aliases[$field] ?? [$this->normalizeHeader($field)];
            $normalizedAllowed = array_map(fn ($alias) => $this->normalizeHeader($alias), $allowed);
            foreach ($this->headers as $header) {
                if (in_array($this->normalizeHeader($header), $normalizedAllowed, true)) {
                    $this->mappings[$field] = $header;
                    break;
                }
            }
        }

        $this->step = 2;
        $this->loadPreview();
        $this->buildFieldMeta();
    }

    public function loadPreview()
    {
        $rows = Excel::toArray(new \stdClass(), Storage::disk('public')->path($this->filePath));
        $this->previewData = array_slice($rows[0], 1, 10);
        $this->previewErrors = $this->validatePreviewRows($rows[0] ?? []);
    }

    public function goToStep($step)
    {
        $this->step = $step;
    }

    public function sample()
    {
        return Excel::download(new FixedAssetImportTemplate(), 'asset_import_template.xlsx');
    }

    public function downloadPreviewErrors()
    {
        return Excel::download(new ErrorsExport($this->previewErrors), 'asset_import_preview_errors.xlsx');
    }

    public function save()
    {
        abort_unless(auth()->user()?->can('asset.import'), 403);
        $this->validate([
            'mappings.name' => 'required',
        ], [
            'mappings.name.required' => 'The Asset Name field must be mapped.',
        ]);

        if (! empty($this->previewErrors)) {
            $this->dispatch('error', ['message' => 'Please fix the validation preview errors before starting the import.']);

            return;
        }

        ImportProductJob::dispatch(
            Auth::id(),
            $this->filePath,
            session('branch_id'),
            session('tenant_id'),
            $this->mappings,
            'asset',
            'Asset'
        );

        $this->dispatch('success', ['message' => 'Import started in background']);
        $this->step = 4;
    }

    public function render()
    {
        return view('livewire.service.import', [
            'units' => Unit::pluck('name', 'id'),
            'departments' => Department::pluck('name', 'id'),
            'categories' => Category::whereNull('parent_id')->pluck('name', 'id'),
            'entityLabel' => $this->entityLabel,
            'progressType' => 'Asset',
            'redirectRoute' => route('asset::index'),
            'fieldMeta' => $this->fieldMeta,
            'previewErrors' => $this->previewErrors,
        ]);
    }

    private function buildFieldMeta(): void
    {
        $this->fieldMeta = [
            'name' => ['instruction' => 'Required. Use the asset name users will search for later.', 'sample' => 'Office Chair'],
            'code' => ['instruction' => 'Recommended. Use your internal asset code or physical tag number.', 'sample' => 'FA-001'],
            'main_category' => ['instruction' => 'Recommended asset group. Existing category name or a new readable group name.', 'sample' => 'Furniture'],
            'purchase_date' => ['instruction' => 'Required for depreciation. Use a valid date.', 'sample' => '2026-01-15'],
            'cost' => ['instruction' => 'Required. Original capitalized cost of the asset.', 'sample' => '3500.00'],
            'mrp' => ['instruction' => 'Optional resale or disposal value only.', 'sample' => '500.00'],
            'duration' => ['instruction' => 'Required for assets. Useful life number only.', 'sample' => '5'],
            'duration_period' => ['instruction' => 'Required. Must be one of days, months, years.', 'sample' => 'years'],
            'depreciation_method' => ['instruction' => 'Required. Must be straight_line or declining_balance.', 'sample' => 'straight_line'],
            'declining_factor' => ['instruction' => 'Needed only for declining balance. Common value is 2.0.', 'sample' => '2.0'],
            'prorata_date' => ['instruction' => 'Optional alternate depreciation start date.', 'sample' => '2026-01-15'],
            'upload_type' => ['instruction' => 'Use new for new assets or update to update an existing asset by name.', 'sample' => 'new'],
        ];
    }

    private function validatePreviewRows(array $rows): array
    {
        $errors = [];
        $previewRows = array_slice($rows, 1, 25);

        foreach ($previewRows as $index => $row) {
            $mapped = [];
            foreach ($this->mappings as $internalField => $excelHeader) {
                if ($excelHeader !== '' && array_key_exists($excelHeader, $row)) {
                    $mapped[$internalField] = $row[$excelHeader];
                }
            }

            if (empty(trim((string) ($mapped['name'] ?? '')))) {
                continue;
            }

            $validator = Validator::make($mapped, [
                'name' => ['required'],
                'purchase_date' => ['required', 'date'],
                'cost' => ['required', 'numeric'],
                'mrp' => ['nullable', 'numeric'],
                'duration' => ['required', 'numeric', 'min:0.01'],
                'duration_period' => ['required', 'in:days,months,years'],
                'depreciation_method' => ['required', 'in:straight_line,declining_balance'],
                'declining_factor' => ['nullable', 'numeric', 'min:0.01'],
                'prorata_date' => ['nullable', 'date'],
                'status' => ['nullable', 'in:active,disabled,inactive'],
                'upload_type' => ['nullable', 'in:new,update'],
            ]);

            if ($validator->fails()) {
                $errors[] = array_merge($mapped, [
                    'row_number' => $index + 2,
                    'message' => $validator->errors()->first(),
                    'file' => 'Asset Import Preview',
                    'line' => $index + 2,
                ]);

                continue;
            }

            $uploadType = strtolower((string) ($mapped['upload_type'] ?? 'new'));
            if ($uploadType === 'update') {
                $existing = Product::asset()->where('name', trim((string) $mapped['name']))->exists();
                if (! $existing) {
                    $errors[] = array_merge($mapped, [
                        'row_number' => $index + 2,
                        'message' => 'Upload type is update, but no existing asset with this name was found.',
                        'file' => 'Asset Import Preview',
                        'line' => $index + 2,
                    ]);
                }
            }
        }

        return $errors;
    }
}
