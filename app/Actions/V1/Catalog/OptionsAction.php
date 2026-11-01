<?php

namespace App\Actions\V1\Catalog;

use App\Actions\Settings\UpdateProductConfigurationAction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Department;
use App\Models\Unit;

/**
 * Everything the catalog filters and the Add / Edit pickers choose from, in one
 * request, plus the barcode settings the form adapts to.
 */
class OptionsAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        $pairs = fn ($query) => $query->orderBy('name')->get(['id', 'name'])->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])->all();
        $config = UpdateProductConfigurationAction::current();

        return [
            'departments' => $pairs(Department::query()),
            'main_categories' => $pairs(Category::query()->whereNull('parent_id')),
            'sub_categories' => Category::query()
                ->whereNotNull('parent_id')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id'])
                ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name, 'parent_id' => $c->parent_id])
                ->all(),
            'units' => $pairs(Unit::query()),
            'brands' => $pairs(Brand::query()),
            // What a new item's unit starts on, as on the web form.
            'default_unit_id' => Unit::defaultBaseUnit()?->id,
            // The web form only asks for a barcode when it is typed per product.
            'barcode_type' => $config['barcode_type'],
            'sync_barcode_to_code' => $config['sync_barcode_to_code'] === 'yes',
        ];
    }
}
