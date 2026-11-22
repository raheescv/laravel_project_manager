<?php

namespace App\Livewire\Product;

use App\Actions\Product\DeleteAction;
use App\Exports\ProductExport;
use App\Jobs\Export\ExportProductJob;
use App\Livewire\Concerns\HasColumnPreferences;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Department;
use App\Models\Product;
use App\Models\Unit;
use App\Support\ProductColours;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use HasColumnPreferences;
    use WithPagination;

    /** Text columns the search box looks in. */
    private const SEARCH_COLUMNS = ['name', 'name_arabic', 'code', 'barcode', 'color'];

    /** Dropdown filters that match their column exactly. */
    private const EXACT_FILTERS = ['department_id', 'main_category_id', 'sub_category_id', 'brand_id', 'unit_id', 'status'];

    /** Columns the headers may sort by. */
    private const SORTABLE = ['id', 'department_id', 'main_category_id', 'sub_category_id', 'unit_id', 'brand_id', 'size', 'color', 'code', 'name', 'name_arabic', 'barcode', 'cost', 'mrp'];

    public $search = '';

    public $department_id = '';

    public $main_category_id = '';

    public $sub_category_id = '';

    public $brand_id = '';

    public $is_selling = '';

    public $unit_id = '';

    public $status = '';

    public $image_status = '';

    public $size = '';

    /** Product code (SKU) filter; exact match, a variant's code leads to its family. */
    public string $sku = '';

    /** '' every product, 'main' only parent_id null (heads + standalone), 'variants' only parent_id set. */
    public string $variant = '';

    public $limit = 50;

    /** Tighter rows; remembered for the session. */
    #[Session(key: 'product.table.compact')]
    public bool $compact = false;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    /** One row per family (head + its size / colour variants) instead of one per variant. */
    public bool $groupVariants = true;

    /** @var array<int, int> family heads whose variants are expanded */
    public array $openFamilies = [];

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Product-Refresh-Component' => '$refresh',
    ];

    /** @var array<string, bool>|null tenant-wide starting columns, read once per request */
    private ?array $tenantDefaultColumns = null;

    public function mount(): void
    {
        $this->initializeColumnPreferences();
    }

    /**
     * Shipped columns, overlaid with the tenant's old shared choice so each user
     * starts from what the shop already had before picking their own.
     *
     * @return array<string, bool>
     */
    protected function defaultColumns(): array
    {
        if ($this->tenantDefaultColumns === null) {
            $shipped = [
                'id' => true,
                'image' => false,
                'department' => true,
                'main_category' => true,
                'sub_category' => true,
                'unit' => true,
                'brand' => true,
                'size' => true,
                'color' => true,
                'code' => true,
                'name_arabic' => true,
                'barcode' => true,
                'cost' => true,
                'mrp' => true,
            ];
            $tenant = json_decode((string) Configuration::where('key', 'product_visible_column')->value('value'), true);
            $this->tenantDefaultColumns = array_merge($shipped, array_map(fn ($visible) => (bool) $visible, array_intersect_key(is_array($tenant) ? $tenant : [], $shipped)));
        }

        return $this->tenantDefaultColumns;
    }

    protected function columnPreferenceKey(): string
    {
        return 'product.table.columns';
    }

    /** @return array<string, string> */
    protected function columnLabels(): array
    {
        return [
            'id' => 'ID',
            'image' => 'Image',
            'name_arabic' => 'Arabic Name',
            'size' => 'Size',
            'color' => 'Color',
            'code' => 'SKU Code',
            'barcode' => 'Barcode',
            'department' => 'Department',
            'main_category' => 'Main Category',
            'sub_category' => 'Sub Category',
            'brand' => 'Brand',
            'unit' => 'Unit',
            'cost' => 'Cost',
            'mrp' => 'MRP',
        ];
    }

    public function delete()
    {
        abort_unless(auth()->user()?->can('product.delete'), 403);
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            DB::commit();
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];

            $this->selectAll = false;
            $this->dispatch('RefreshProductTable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updateSelling(bool $isSelling): void
    {
        $this->bulkUpdateFlag('is_selling', $isSelling, $isSelling ? 'selling' : 'not selling');
    }

    public function updateFavorite(bool $isFavorite): void
    {
        $this->bulkUpdateFlag('is_favorite', $isFavorite, $isFavorite ? 'favorite' : 'not favorite');
    }

    /** Sets one boolean column on every selected product inside a single transaction. */
    private function bulkUpdateFlag(string $column, bool $value, string $label): void
    {
        abort_unless(auth()->user()?->can('product.edit'), 403);
        if (! count($this->selected)) {
            $this->dispatch('error', ['message' => 'Please select any item to update.']);

            return;
        }
        try {
            DB::beginTransaction();
            $products = Product::product()->whereIn('id', $this->selected)->get();
            foreach ($products as $product) {
                $product->update([$column => $value]);
            }
            DB::commit();
            $this->dispatch('success', ['message' => 'Marked '.$products->count().' items as '.$label]);
            $this->clearSelection();
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['selectAll', 'openFamilies', 'compact', 'columns']) && ! preg_match('/^(selected|columns)\..*/', $key)) {
            $this->resetPage();
        }
    }

    /** Variants are hidden in grouped mode, so asking for them switches to one row per variant. */
    public function updatedVariant(string $value): void
    {
        if ($value === 'variants') {
            $this->groupVariants = false;
        }
    }

    public function updatedGroupVariants(bool $value): void
    {
        if ($value && $this->variant === 'variants') {
            $this->variant = '';
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = Product::product()->latest()->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    /** Resets one filter chip; the browser clears the matching TomSelect. */
    public function clearFilter(string $key): void
    {
        if (! array_key_exists($key, $this->filterLabels())) {
            return;
        }
        $this->{$key} = '';
        $this->resetPage();
        $this->dispatch('product-filters-cleared', keys: [$key]);
    }

    public function clearFilters(): void
    {
        $keys = array_keys($this->activeFilters());
        foreach ($keys as $key) {
            $this->{$key} = '';
        }
        $this->resetPage();
        $this->dispatch('product-filters-cleared', keys: $keys);
    }

    /**
     * Applied filters as removable chips, keyed by property.
     *
     * @return array<string, array{label: string, value: string}>
     */
    public function activeFilters(): array
    {
        $names = [
            'department_id' => fn ($id) => Department::query()->whereKey($id)->value('name'),
            'main_category_id' => fn ($id) => Category::query()->whereKey($id)->value('name'),
            'sub_category_id' => fn ($id) => Category::query()->whereKey($id)->value('name'),
            'brand_id' => fn ($id) => Brand::query()->whereKey($id)->value('name'),
            'unit_id' => fn ($id) => Unit::query()->whereKey($id)->value('name'),
            'status' => fn ($value) => activeOrDisabled()[$value] ?? $value,
            'image_status' => fn ($value) => ['with_image' => 'With image', 'without_image' => 'Without image'][$value] ?? $value,
            'variant' => fn ($value) => ['main' => 'Main products only', 'variants' => 'Variants only'][$value] ?? $value,
            'is_selling' => fn () => 'Currently selling',
        ];

        $chips = [];
        foreach ($this->filterLabels() as $key => $label) {
            $value = $this->{$key};
            if ($value === '' || $value === null || $value === false) {
                continue;
            }
            $chips[$key] = ['label' => $label, 'value' => (string) (isset($names[$key]) ? ($names[$key]($value) ?? $value) : trim((string) $value))];
        }

        return $chips;
    }

    /** @return array<string, string> filter property => chip label */
    private function filterLabels(): array
    {
        return [
            'sku' => 'SKU',
            'department_id' => 'Department',
            'main_category_id' => 'Category',
            'sub_category_id' => 'Sub category',
            'brand_id' => 'Brand',
            'unit_id' => 'Unit',
            'size' => 'Size',
            'status' => 'Status',
            'image_status' => 'Image',
            'variant' => 'Variant',
            'is_selling' => 'Sales',
        ];
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('product.export'), 403);
        $filter = [
            'type' => 'product',
            'department_id' => $this->department_id,
            'main_category_id' => $this->main_category_id,
            'sub_category_id' => $this->sub_category_id,
            'brand_id' => $this->brand_id,
            'is_selling' => $this->is_selling,
            'unit_id' => $this->unit_id,
            'status' => $this->status,
            'image_status' => $this->image_status,
            'variant' => $this->variant,
        ];
        $count = Product::service()->count();
        if ($count > 2000) {
            ExportProductJob::dispatch(Auth::user(), $filter);
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'Product_'.now()->timestamp.'.xlsx';

            return Excel::download(new ProductExport($filter), $exportFileName);
        }
    }

    public function toggleFamily(int $id): void
    {
        $this->openFamilies = in_array($id, $this->openFamilies, true)
            ? array_values(array_diff($this->openFamilies, [$id]))
            : [...$this->openFamilies, $id];
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        return view('livewire.product.table', [
            'data' => $this->productsQuery()->paginate($this->limit),
            'colourPalette' => ProductColours::palette(),
            'activeFilters' => $this->activeFilters(),
            'columnLabels' => $this->columnLabels(),
        ]);
    }

    /**
     * The list query. In grouped mode a family is listed (by its head) when the
     * head or any of its variants matches the search, size or SKU code.
     */
    protected function productsQuery(): Builder
    {
        $terms = preg_split('/\s+/', trim((string) $this->search), -1, PREG_SPLIT_NO_EMPTY);
        $size = trim((string) $this->size);
        $sku = trim($this->sku);

        $query = Product::query()
            ->product()
            ->with(['brand:id,name', 'department:id,name', 'mainCategory:id,name', 'subCategory:id,name', 'unit:id,name'])
            ->withCount('variants')
            ->when($this->groupVariants, fn (Builder $query) => $query->familyHeads()
                ->with(['variants' => fn ($variants) => $variants->select(['id', 'parent_id', 'name', 'code', 'barcode', 'size', 'color', 'cost', 'mrp'])->orderBy('id')]))
            ->when($terms, fn (Builder $query) => $this->whereFamilyMatches($query, fn (Builder $member) => $this->matchSearchTerms($member, $terms)))
            ->when($size !== '', fn (Builder $query) => $this->whereFamilyMatches($query, fn (Builder $member) => $member->where('size', $size)))
            ->when($sku !== '', fn (Builder $query) => $this->whereFamilyMatches($query, fn (Builder $member) => $member->where('code', '=', $sku)))
            ->when($this->is_selling, fn (Builder $query) => $query->where('is_selling', true))
            ->when($this->image_status === 'with_image', fn (Builder $query) => $query->whereHas('images'))
            ->when($this->image_status === 'without_image', fn (Builder $query) => $query->whereDoesntHave('images'))
            ->when($this->variant === 'main', fn (Builder $query) => $query->whereNull('products.parent_id'))
            ->when($this->variant === 'variants', fn (Builder $query) => $query->whereNotNull('products.parent_id'));

        foreach (self::EXACT_FILTERS as $column) {
            $query->when($this->{$column}, fn (Builder $query, $value) => $query->where($column, $value));
        }

        return $query
            ->orderBy(in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'id', $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id');
    }

    /**
     * Every word must appear in one of the text columns ("shoe black" finds a
     * black running shoe); a number also matches an exact cost or price.
     *
     * @param  array<int, string>  $terms
     */
    protected function matchSearchTerms(Builder $query, array $terms): void
    {
        foreach ($terms as $term) {
            $query->where(function (Builder $match) use ($term): void {
                foreach (self::SEARCH_COLUMNS as $column) {
                    $match->orWhere($column, 'like', "%{$term}%");
                }
                if (is_numeric($term)) {
                    $match->orWhere('mrp', $term)->orWhere('cost', $term);
                }
            });
        }
    }

    /** Grouped: keep heads whose family has a matching member (one subquery). Flat: match the row itself. */
    protected function whereFamilyMatches(Builder $query, callable $constraint): Builder
    {
        if (! $this->groupVariants) {
            return $query->where($constraint);
        }

        return $query->whereIn('products.id', Product::query()->selectRaw('COALESCE(parent_id, id)')->where($constraint));
    }
}
