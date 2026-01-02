<?php

namespace App\Actions\Product\Inventory;

use App\Models\Inventory;

class GetAction
{
    public function execute($filter)
    {
        return Inventory::query()
            ->join('branches', 'inventories.branch_id', '=', 'branches.id')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('departments', 'products.department_id', '=', 'departments.id')
            ->join('units', 'products.unit_id', '=', 'units.id')
            ->join('categories as main_categories', 'products.main_category_id', '=', 'main_categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories as sub_categories', 'products.sub_category_id', '=', 'sub_categories.id')
            ->where('products.type', 'product')
            ->when($filter['search'] ?? '', function ($query, $value) {
                $value = trim($value);

                return $query->where(function ($q) use ($value): void {
                    $q->where('products.name', 'like', "%{$value}%")
                        ->orWhere('products.name_arabic', 'like', "%{$value}%")
                        ->orWhere('products.code', 'like', "%{$value}%")
                        ->orWhere('branches.name', 'like', "%{$value}%")
                        ->orWhere('departments.name', 'like', "%{$value}%")
                        ->orWhere('units.name', 'like', "%{$value}%")
                        ->orWhere('brands.name', 'like', "%{$value}%")
                        ->orWhere('main_categories.name', 'like', "%{$value}%")
                        ->orWhere('sub_categories.name', 'like', "%{$value}%")
                        ->orWhere('inventories.barcode', 'like', "%{$value}%")
                        ->orWhere('inventories.batch', 'like', "%{$value}%")
                        ->orWhere('products.size', $value)
                        ->orWhere('inventories.quantity', 'like', "%{$value}%")
                        ->orWhere('inventories.cost', 'like', "%{$value}%");
                });
            })
            ->when($filter['code'] ?? '', function ($query, $value) {
                return $query->where('products.code', $value);
            })
            ->when($filter['brand_id'] ?? '', function ($query, $value) {
                return $query->where('products.brand_id', $value);
            })
            ->when($filter['department_id'] ?? '', function ($query, $value) {
                return $query->where('products.department_id', $value);
            })
            ->when($filter['main_category_id'] ?? '', function ($query, $value) {
                return $query->where('products.main_category_id', $value);
            })
            ->when($filter['size'] ?? '', function ($query, $value) {
                return $query->where('products.size', $value);
            })
            ->when($filter['product_name'] ?? '', function ($query, $value) {
                $value = trim($value);

                return $query->where('products.name', 'like', "%{$value}%");
            })
            ->when($filter['barcode'] ?? '', function ($query, $value) {
                return $query->where('inventories.barcode', $value);
            })
            ->when($filter['sub_category_id'] ?? '', function ($query, $value) {
                return $query->where('products.sub_category_id', $value);
            })
            ->when($filter['unit_id'] ?? '', function ($query, $value) {
                return $query->where('products.unit_id', $value);
            })
            ->when($filter['non_zero'] ?? '', function ($query, $value) {
                return $query->where('inventories.quantity', '!=', 0);
            })
            ->when($filter['branch_id'] ?? '', function ($query, $value) {
                return $query->whereIn('inventories.branch_id', $value);
            })
            ->when($filter['product_id'] ?? '', function ($query, $value) {
                return $query->where('inventories.product_id', $value);
            })
            ->when(! isset($filter['include_employee_inventory']) || ! $filter['include_employee_inventory'], function ($query) {
                // By default, only show branch inventories (employee_id is null)
                return $query->whereNull('inventories.employee_id');
            })
            ->orderBy($filter['sortField'] ?? '', $filter['sortDirection'] ?? '');
    }
}
