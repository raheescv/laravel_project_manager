<?php

namespace App\Actions\Product;

use App\Actions\Product\Inventory\GetAction;
use Illuminate\Support\Facades\DB;

class InventoryProductWiseAction
{
    public function execute($params = [])
    {
        $query = (new GetAction())->execute($params)
            ->groupBy('products.id')
            ->select(
                'products.id',
                'products.name',
                'products.code',
                'products.name_arabic',
                'departments.name as department_name',
                'main_categories.name as main_category_name',
                'sub_categories.name as sub_category_name',
                'brands.name as brand_name',
                'units.name as unit_name',
                'products.size',
                DB::raw('SUM(inventories.quantity) as total_quantity'),
                DB::raw('AVG(inventories.cost) as average_cost'),
                DB::raw('SUM(inventories.total) as total_value')
            );

        return $query;
    }
}
