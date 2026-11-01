<?php

namespace App\Actions\V1\Catalog;

use App\Http\Requests\V1\Catalog\ListRequest;
use App\Http\Resources\V1\Catalog\CatalogItemResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;

/**
 * The Settings → Catalog table for one type (product | service): the web
 * Product / Service table's search and filters, sorting by any column, and the
 * KPI counts shown above it.
 */
class ListAction
{
    /** Columns sorted on the products table itself. */
    private const COLUMN_SORTS = ['id', 'name', 'name_arabic', 'code', 'barcode', 'size', 'cost', 'mrp', 'priority', 'time'];

    /**
     * Columns shown by name but stored as a foreign key. They sort by the
     * related name — sorting by the id (as the web table does) reads as random.
     *
     * @var array<string, array{0: class-string, 1: string}>
     */
    private const RELATION_SORTS = [
        'department' => [Department::class, 'department_id'],
        'main_category' => [Category::class, 'main_category_id'],
        'sub_category' => [Category::class, 'sub_category_id'],
        'unit' => [Unit::class, 'unit_id'],
        'brand' => [Brand::class, 'brand_id'],
    ];

    /**
     * @return list<string>
     */
    public static function sortableFields(): array
    {
        return [...self::COLUMN_SORTS, ...array_keys(self::RELATION_SORTS)];
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, pagination: array<string, mixed>, summary: array<string, int|float>}
     */
    public function execute(string $type, ListRequest $request): array
    {
        $search = trim((string) $request->validated('search'));

        $query = Product::query()
            ->where('type', $type)
            ->with(['department:id,name', 'mainCategory:id,name', 'subCategory:id,name', 'unit:id,name', 'brand:id,name'])
            ->when($search, fn (Builder $q, string $value) => $q->where(function (Builder $w) use ($value): void {
                $w->where('name', 'like', "%{$value}%")
                    ->orWhere('name_arabic', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%")
                    ->orWhere('barcode', 'like', "%{$value}%");
            }))
            ->when($request->validated('department_id'), fn (Builder $q, $value) => $q->where('department_id', $value))
            ->when($request->validated('main_category_id'), fn (Builder $q, $value) => $q->where('main_category_id', $value))
            ->when($request->validated('brand_id'), fn (Builder $q, $value) => $q->where('brand_id', $value))
            ->when($request->validated('status'), fn (Builder $q, $value) => $q->where('status', $value));

        $this->applySort($query, $request->validated('sort') ?? 'id', $request->validated('direction') ?? 'desc');

        $page = $query->paginate((int) ($request->validated('per_page') ?? 30));

        return [
            'data' => CatalogItemResource::collection($page->items())->resolve(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'has_more_pages' => $page->hasMorePages(),
            ],
            'summary' => $this->summary($type),
        ];
    }

    private function applySort(Builder $query, string $field, string $direction): void
    {
        if (isset(self::RELATION_SORTS[$field])) {
            [$model, $foreignKey] = self::RELATION_SORTS[$field];
            $table = (new $model())->getTable();
            $query->orderBy($model::select('name')->whereColumn("{$table}.id", "products.{$foreignKey}")->limit(1), $direction);
        } else {
            $query->orderBy("products.{$field}", $direction);
        }
        // Equal values (e.g. one department) would otherwise page unpredictably.
        $query->orderBy('products.id', 'desc');
    }

    /**
     * Whole-catalog counts for the KPI cards — unaffected by search and filters.
     *
     * @return array{total: int, selling: int, disabled: int, avg_time: float}
     */
    private function summary(string $type): array
    {
        $row = Product::query()
            ->where('type', $type)
            ->selectRaw('COUNT(*) as total, SUM(is_selling = 1) as selling, SUM(status = ?) as disabled, AVG(time) as avg_time', ['disabled'])
            ->first();

        return [
            'total' => (int) $row?->total,
            'selling' => (int) $row?->selling,
            'disabled' => (int) $row?->disabled,
            'avg_time' => round((float) $row?->avg_time, 1),
        ];
    }
}
