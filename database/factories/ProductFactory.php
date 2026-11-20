<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Products are tenant-scoped (BelongsToTenant fills tenant_id), so set the
 * current tenant before using this factory.
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'product',
            'name' => 'Product '.Str::upper(Str::random(8)),
            'code' => 'P'.Str::upper(Str::random(8)),
            'barcode_number' => (string) fake()->unique()->numberBetween(10_000_000, 99_999_999),
            'unit_id' => fn () => Unit::create(['name' => 'Piece '.Str::random(4), 'code' => 'PC'])->id,
            'department_id' => fn () => Department::create(['name' => 'General '.Str::random(4)])->id,
            'main_category_id' => fn () => Category::create(['name' => 'General '.Str::random(4)])->id,
            'cost' => 10,
            'mrp' => 20,
            'tax' => 0,
            'is_selling' => true,
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    /** A size / colour variant of [$parent], sharing the head's family fields. */
    public function variantOf(Product $parent, string $size, ?string $color = null): static
    {
        $parent = $parent->fresh() ?? $parent;

        return $this->state(fn (): array => array_merge($parent->only(Product::VARIANT_SHARED_FIELDS), [
            'parent_id' => $parent->id,
            'size' => $size,
            'color' => $color,
            'cost' => $parent->cost,
            'mrp' => $parent->mrp,
        ]));
    }
}
