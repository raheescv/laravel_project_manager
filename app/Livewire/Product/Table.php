<?php

namespace App\Livewire\Product;

use App\Actions\Product\DeleteAction;
use App\Exports\ProductExport;
use App\Jobs\Export\ExportProductJob;
use App\Models\Configuration;
use App\Models\Product;
use App\Support\ProductColours;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
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

    /** '' every product, 'main' only parent_id null (heads + standalone), 'variants' only parent_id set. */
    public string $variant = '';

    public $limit = 100;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    public $product_visible_column = [];

    /** One row per family (head + its size / colour variants) instead of one per variant. */
    public bool $groupVariants = true;

    /** @var array<int, int> family heads whose variants are expanded */
    public array $openFamilies = [];

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Product-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $config = Configuration::where('key', 'product_visible_column')->value('value');
        $this->product_visible_column = array_merge($this->getDefaultColumns(), $config ? (array) json_decode($config, true) : []);
    }

    protected function getDefaultColumns()
    {
        return [
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
            'product_name' => true,
            'name_arabic' => true,
            'barcode' => true,
            'cost' => true,
            'mrp' => true,
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
        abort_unless(auth()->user()?->can('product.edit'), 403);
        if (! count($this->selected)) {
            $this->dispatch('error', ['message' => 'Please select any item to update.']);

            return;
        }
        try {
            DB::beginTransaction();
            $products = Product::product()->whereIn('id', $this->selected)->get();
            foreach ($products as $product) {
                $product->update(['is_selling' => $isSelling]);
            }
            DB::commit();
            $label = $isSelling ? 'selling' : 'not selling';
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
        if (! in_array($key, ['selectAll', 'openFamilies']) && ! preg_match('/^selected\..*/', $key)) {
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
        ]);
    }

    /**
     * The list query. In grouped mode a family is listed (by its head) when the
     * head or any of its variants matches the search or size.
     */
    protected function productsQuery(): Builder
    {
        $terms = preg_split('/\s+/', trim((string) $this->search), -1, PREG_SPLIT_NO_EMPTY);
        $size = trim((string) $this->size);

        $query = Product::query()
            ->product()
            ->with(['brand:id,name', 'department:id,name', 'mainCategory:id,name', 'subCategory:id,name', 'unit:id,name'])
            ->withCount('variants')
            ->when($this->groupVariants, fn (Builder $query) => $query->familyHeads()
                ->with(['variants' => fn ($variants) => $variants->select(['id', 'parent_id', 'name', 'code', 'barcode', 'size', 'color', 'cost', 'mrp'])->orderBy('id')]))
            ->when($terms, fn (Builder $query) => $this->whereFamilyMatches($query, fn (Builder $member) => $this->matchSearchTerms($member, $terms)))
            ->when($size !== '', fn (Builder $query) => $this->whereFamilyMatches($query, fn (Builder $member) => $member->where('size', $size)))
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
