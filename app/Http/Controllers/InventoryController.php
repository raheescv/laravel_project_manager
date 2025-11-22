<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }

    //  public function search()
    //     {
    //         return view('inventory.product-search');
    //     }
    public function search()
    {
        return Inertia::render('Inventory/ProductSearch');
    }

    public function view($product_id)
    {
        $product = Product::find($product_id);

        return view('inventory.view', compact('product_id', 'product'));
    }

    // public function get(Request $request)
    // {
    //     $list = (new Inventory())->getDropDownList($request->all());

    //     return response()->json($list);
    // }

    public function get(Request $request)
    {
        $limit = intval($request->input('limit', 10));
        $page = intval($request->input('page', 1));
        $productName = trim($request->input('productName', ''));
        $productCode = trim($request->input('productCode', ''));
        $productBarcode = trim($request->input('productBarcode', ''));
        $branchIds = $request->input('branch_id', null);
        $showNonZero = intval($request->input('show_non_zero', 0));
        $showBarcodeSku = intval($request->input('show_barcode_sku', 0));
        $sortField = $request->input('sortField', 'products.code');
        $sortDirection = $request->input('sortDirection', 'desc');

        // Build base query
        $query = DB::table('inventories')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->join('branches', 'inventories.branch_id', '=', 'branches.id')
            ->where('products.type', '=', 'product');

        if ($productName !== '') {
            $query->where('products.name', 'like', "%{$productName}%");
        }
        if ($productCode !== '') {
            $query->where('products.code', 'like', "%{$productCode}%");
        }
        if ($productBarcode !== '') {
            $query->where('inventories.barcode', 'like', "%{$productBarcode}%");
        }

        if (! empty($branchIds)) {
            if (! is_array($branchIds)) {
                // Accept comma-separated string
                $branchIds = explode(',', $branchIds);
            }
            $branchIds = array_map('intval', $branchIds);
            $query->whereIn('inventories.branch_id', $branchIds);
        }

        if ($showNonZero) {
            $query->where('inventories.quantity', '>', 0);
        }

        if ($showBarcodeSku) {
            $query->whereNotNull('inventories.barcode')->where('inventories.barcode', '<>', '');
        }

        // Allowed sort map to avoid injection
        $allowedSorts = [
            'products.code' => 'products.code',
            'products.name' => 'products.name',
            'products.size' => 'products.size',
            'inventories.barcode' => 'inventories.barcode',
            'products.mrp' => 'products.mrp',
            'branches.name' => 'branches.name',
            'inventories.quantity' => 'inventories.quantity',
            'inventories.id' => 'inventories.id',
        ];

        if (! array_key_exists($sortField, $allowedSorts)) {
            $sortField = 'products.code';
        }
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->select(
            'inventories.id as inventory_id',
            'products.id as id',
            'products.code',
            'products.name',
            'products.size',
            'inventories.barcode',
            'products.mrp',
            'branches.name as branch_name',
            'inventories.quantity'
        );

        $query->orderBy($allowedSorts[$sortField], $sortDirection);

        // total (filtered) count for pagination
        $total = (clone $query)->count();

        // fetch paginated rows
        $rows = $query->forPage($page, $limit)->get();

        // total quantity across filtered set (not just page)
        $quantityQuery = DB::table('inventories')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->when($productName !== '', function ($q) use ($productName) {
                $q->where('products.name', 'like', "%{$productName}%");
            })
            ->when($productCode !== '', function ($q) use ($productCode) {
                $q->where('products.code', 'like', "%{$productCode}%");
            })
            ->when($productBarcode !== '', function ($q) use ($productBarcode) {
                $q->where('inventories.barcode', 'like', "%{$productBarcode}%");
            })
            ->when(! empty($branchIds), function ($q) use ($branchIds) {
                $q->whereIn('inventories.branch_id', $branchIds);
            })
            ->when($showNonZero, function ($q) {
                $q->where('inventories.quantity', '>', 0);
            })
            ->when($showBarcodeSku, function ($q) {
                $q->whereNotNull('inventories.barcode')->where('inventories.barcode', '<>', '');
            })
            ->where('products.type', '=', 'product');

        $totalQuantity = (int) $quantityQuery->sum('inventories.quantity');

        // transform rows to simple arrays
        $data = $rows->map(function ($r) {
            return [
                'inventory_id' => $r->inventory_id,
                'id' => $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'size' => $r->size,
                'barcode' => $r->barcode,
                'mrp' => $r->mrp,
                'branch_name' => $r->branch_name,
                'quantity' => (int) $r->quantity,
            ];
        })->toArray();

        $lastPage = (int) ceil($total / max(1, $limit));

        // Return JSON in the confirmed shape
        return response()->json([
            'data' => $data,
            'total_quantity' => $totalQuantity,
            'links' => [
                'current_page' => $page,
                'last_page' => $lastPage,
            ],
            'per_page' => $limit,
            'total' => $total,
        ]);
    }
}
