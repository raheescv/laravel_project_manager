<?php

namespace App\Actions\Product\Inventory\StockCheck\Item;

use App\Models\StockCheck;
use App\Models\StockCheckItem;
use Exception;
use Illuminate\Http\Request;

class GetStockCheckItemsAction
{
    public function execute(int $stockCheckId, Request $request): array
    {
        try {
            $stockCheck = StockCheck::findOrFail($stockCheckId);

            $query = StockCheckItem::where('stock_check_id', $stockCheckId)
                ->with(['product.brand', 'product.mainCategory'])
                ->join('products', 'stock_check_items.product_id', '=', 'products.id')
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
                ->leftJoin('categories', 'products.main_category_id', '=', 'categories.id')
                ->leftJoin('inventories', function ($join) use ($stockCheck) {
                    $join->on('inventories.product_id', '=', 'products.id')
                        ->where('inventories.branch_id', '=', $stockCheck->branch_id)
                        ->whereNull('inventories.employee_id');
                });

            // Apply filters
            if ($request->filled('category_id')) {
                $query->where('products.main_category_id', $request->category_id);
            }

            if ($request->filled('brand_id')) {
                $query->where('products.brand_id', $request->brand_id);
            }

            if ($request->filled('recorded_qty_condition')) {
                if ($request->recorded_qty_condition === 'non_zero') {
                    $query->where('stock_check_items.recorded_quantity', '!=', 0);
                } elseif ($request->recorded_qty_condition === 'zero') {
                    $query->where('stock_check_items.recorded_quantity', '=', 0);
                }
            }

            if ($request->filled('status')) {
                $query->where('stock_check_items.status', $request->status);
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('products.name', 'like', "%{$search}%")
                        ->orWhere('products.code', 'like', "%{$search}%")
                        ->orWhere('inventories.barcode', 'like', "%{$search}%");
                });
            }

            if ($request->filled('barcode')) {
                $query->where('inventories.barcode', $request->barcode);
            }

            // Sorting
            $sortField = $request->get('sort_field', 'stock_check_items.updated_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            // Select columns with calculated difference
            $query->select([
                'stock_check_items.id',
                'stock_check_items.stock_check_id',
                'stock_check_items.product_id',
                'stock_check_items.physical_quantity',
                'stock_check_items.recorded_quantity',
                'stock_check_items.status',
                'stock_check_items.updated_at',
                'products.name as product_name',
                'products.code as product_code',
                'brands.name as brand_name',
                'categories.name as category_name',
                'inventories.barcode',
                'stock_check_items.difference',
            ]);

            // Apply difference filter after selecting
            if ($request->filled('difference_condition')) {
                $operator = match ($request->difference_condition) {
                    'positive' => '>',
                    'negative' => '<',
                    'zero' => '=',
                    default => '!=',
                };
                $query->havingRaw('stock_check_items.difference '.$operator.' 0');
            }

            // Apply sorting
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 20);
            $items = $query->paginate($perPage);

            return [
                'success' => true,
                'message' => 'Items retrieved successfully',
                'data' => $items,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }
}
