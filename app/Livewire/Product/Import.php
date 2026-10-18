<?php

namespace App\Livewire\Product;

use App\Exports\Templates\ProductImportTemplate;
use App\Imports\LimitedRowImport;
use App\Jobs\Product\ImportProductImagesFromDropboxJob;
use App\Jobs\Product\ImportProductJob;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Department;
use App\Models\Unit;
use App\Services\ProductImageFolderMatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class Import extends Component
{
    use WithFileUploads;

    public string $entityLabel = 'Product';

    public string $entityPluralLabel = 'Products';

    public $file;

    public $step = 1;

    public $headers = [];

    public $mappings = [];

    public $previewData = [];

    public $filePath;

    public string $stepOneTab = 'spreadsheet';

    public $dropboxFolderUrl = '';

    public $dropboxMatchSummary = null;

    public $dropboxImportSummary = null;

    public bool $dropboxImportQueued = false;

    public $duplicateStrategy = 'skip'; // skip | update

    // Available fields for mapping
    public $availableFields = [
        'id' => 'Product ID (primary match)',
        'name' => 'Product Name English(*)',
        'name_arabic' => 'Product Name Arabic (*)',
        'code' => 'Product Code (SKU)',
        'barcode' => 'Barcode',
        'cost' => 'Buying Price',
        'mrp' => 'Selling Price',
        'tax' => 'Tax Rate',
        'hsn_code' => 'HSN Code',
        'unit' => 'Unit (ID or Name)',
        'department' => 'Department (ID or Name)',
        'main_category' => 'Main Category (ID or Name)',
        'sub_category' => 'Sub Category (ID or Name)',
        'brand' => 'Brand (ID or Name)',
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
        'image' => 'Image URL(s)',
    ];

    /** @var array<int, array{name: string, code: ?string}> branch id => branch, one stock column each */
    public array $stockBranches = [];

    public function mount(): void
    {
        $currentBranchId = (int) session('branch_id');
        $this->stockBranches = Branch::query()
            ->orderByRaw('id = ? desc', [$currentBranchId])
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->mapWithKeys(fn (Branch $branch) => [$branch->id => ['name' => $branch->name, 'code' => $branch->code]])
            ->all();

        $branchFields = [];
        foreach ($this->stockBranches as $branchId => $branch) {
            $suffix = $branchId === $currentBranchId ? ' (current)' : '';
            $branchFields['stock_branch_'.$branchId] = 'Stock — '.$branch['name'].$suffix;
        }

        $position = array_search('stock', array_keys($this->availableFields), true);
        $this->availableFields = array_slice($this->availableFields, 0, $position, true)
            + $branchFields
            + array_slice($this->availableFields, $position + 1, null, true);
    }

    /**
     * Names of the branches whose stock column is mapped; each gets a draft purchase.
     *
     * @return array<int, string>
     */
    public function mappedStockBranchNames(): array
    {
        return collect($this->stockBranches)
            ->filter(fn ($branch, $branchId) => ! empty($this->mappings['stock_branch_'.$branchId] ?? ''))
            ->pluck('name')
            ->all();
    }

    /**
     * Header variants that auto-map to each branch's stock field. The current branch also
     * claims the plain stock headers, so single-branch sheets keep working.
     *
     * @return array<string, array<int, string>>
     */
    private function getBranchStockAliases(): array
    {
        $currentBranchId = (int) session('branch_id');
        $aliases = [];
        foreach ($this->stockBranches as $branchId => $branch) {
            $labels = array_filter([$branch['name'], $branch['code']]);
            $variants = ['stock_branch_'.$branchId];
            foreach ($labels as $label) {
                array_push($variants, 'stock '.$label, $label.' stock', 'qty '.$label, $label.' qty', 'quantity '.$label, $label.' quantity', $label);
            }
            if ($branchId === $currentBranchId) {
                array_push($variants, 'stock', 'openingstock', 'opening stock', 'quantity');
            }
            $aliases['stock_branch_'.$branchId] = $variants;
        }

        return $aliases;
    }

    /**
     * Allowed Excel header variants per internal field (normalized for comparison).
     * Used for accurate mapping so e.g. "name" does not match "arabic_name".
     */
    private function getHeaderAliases(): array
    {
        return [
            'id' => ['id', 'product_id', 'productid', 'product id'],
            'name' => ['name', 'productname', 'productnameenglish', 'product name', 'product name english'],
            'name_arabic' => ['name_arabic', 'namearabic', 'arabicname', 'productnamearabic', 'product name arabic', 'arabic name', 'name arabic'],
            'code' => ['code', 'sku', 'productcode', 'product code'],
            'barcode' => ['barcode'],
            'cost' => ['cost', 'buyingprice', 'buying price', 'purchaseprice'],
            'mrp' => ['mrp', 'price', 'sellingprice', 'selling price'],
            'tax' => ['tax', 'taxrate', 'tax rate'],
            'hsn_code' => ['hsn_code', 'hsncode', 'hsn code'],
            'unit' => ['unit', 'unitid', 'unit'],
            'department' => ['department', 'departmentid', 'department'],
            'main_category' => ['main_category', 'maincategoryid', 'main_category', 'maincategory', 'main category'],
            'sub_category' => ['sub_category', 'subcategoryid', 'sub_category', 'subcategory', 'sub category'],
            'brand' => ['brand', 'brandid', 'brand'],
            'size' => ['size'],
            'color' => ['color'],
            'model' => ['model'],
            'pattern' => ['pattern'],
            'description' => ['description'],
            'is_selling' => ['is_selling', 'isselling', 'is selling'],
            'is_favorite' => ['is_favorite', 'isfavorite', 'is favorite'],
            'part_no' => ['part_no', 'partno', 'part no'],
            'min_stock' => ['min_stock', 'minstock', 'min stock'],
            'max_stock' => ['max_stock', 'maxstock', 'max stock'],
            'location' => ['location'],
            'reorder_level' => ['reorder_level', 'reorderlevel', 'reorder level'],
            'plu' => ['plu'],
            'status' => ['status', 'active', 'inactive'],
            'image' => ['image', 'images', 'image_url', 'imageurl', 'image url', 'image_urls', 'photo', 'picture', 'thumbnail'],
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

        $aliases = $this->getHeaderAliases() + $this->getBranchStockAliases();

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

    public function loadPreview(): void
    {
        $this->previewData = (new LimitedRowImport(10))->toArray(
            Storage::disk('public')->path($this->filePath)
        );
    }

    public function goToStep($step)
    {
        $this->step = $step;
    }

    public function setStepOneTab(string $tab): void
    {
        if (! in_array($tab, ['spreadsheet', 'images'], true)) {
            return;
        }

        $this->stepOneTab = $tab;
    }

    public function sample()
    {
        return Excel::download(new ProductImportTemplate(array_column($this->stockBranches, 'name')), 'product_import_template.xlsx');
    }

    public function save()
    {
        abort_unless(auth()->user()?->can('product.import'), 403);
        $this->validate([
            'mappings.name' => 'required',
        ], [
            'mappings.name.required' => 'The Product Name field must be mapped.',
        ]);

        ImportProductJob::dispatch(
            Auth::id(),
            $this->filePath,
            session('branch_id'),
            session('tenant_id'),
            $this->mappings,
            'product',
            'Product',
            $this->duplicateStrategy
        );

        $this->dispatch('success', ['message' => 'Import started in background']);
        $this->step = 4;
    }

    public function checkDropboxFolderMatches(ProductImageFolderMatcher $matcher): void
    {
        $this->stepOneTab = 'images';

        $this->validate([
            'dropboxFolderUrl' => 'required|url',
        ], [
            'dropboxFolderUrl.required' => 'Please enter a Dropbox folder link.',
            'dropboxFolderUrl.url' => 'Please enter a valid Dropbox folder link.',
        ]);

        try {
            $downloadUrl = $matcher->buildDropboxDownloadUrl($this->dropboxFolderUrl);
            $temporaryZipPath = tempnam(sys_get_temp_dir(), 'dropbox-folder-');

            if ($temporaryZipPath === false) {
                throw new \RuntimeException('Unable to create a temporary file for Dropbox download.');
            }

            $response = Http::timeout(300)
                ->withOptions(['sink' => $temporaryZipPath, 'allow_redirects' => true])
                ->get($downloadUrl);

            if (! $response->successful()) {
                @unlink($temporaryZipPath);

                throw new \RuntimeException('Dropbox folder download failed.');
            }

            try {
                $this->dropboxMatchSummary = $matcher->summarizeMatchesFromZip($temporaryZipPath);
            } finally {
                @unlink($temporaryZipPath);
            }

            $this->dropboxImportSummary = null;
            $this->dropboxImportQueued = false;
        } catch (\Throwable $exception) {
            $this->dropboxMatchSummary = null;
            $this->dropboxImportSummary = null;
            $this->dropboxImportQueued = false;
            $this->dispatch('error', ['message' => $exception->getMessage()]);
        }
    }

    public function importDropboxFolderImages(): void
    {
        abort_unless(auth()->user()?->can('product.import'), 403);
        $this->stepOneTab = 'images';

        $this->validate([
            'dropboxFolderUrl' => 'required|url',
        ], [
            'dropboxFolderUrl.required' => 'Please enter a Dropbox folder link.',
            'dropboxFolderUrl.url' => 'Please enter a valid Dropbox folder link.',
        ]);

        try {
            ImportProductImagesFromDropboxJob::dispatch(
                $this->dropboxFolderUrl,
                session('tenant_id')
            );

            $this->dropboxImportSummary = null;
            $this->dropboxImportQueued = true;
            $this->dispatch('success', ['message' => 'Dropbox image import queued. Images will be imported in the background.']);
        } catch (\Throwable $exception) {
            $this->dropboxImportSummary = null;
            $this->dropboxImportQueued = false;
            $this->dispatch('error', ['message' => $exception->getMessage()]);
        }
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
