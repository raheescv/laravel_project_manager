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

    public function get(Request $request)
    {
        $list = (new Inventory())->getDropDownList($request->all());

        return response()->json($list);
}

   public function getProduct(Request $request)
{
    $barcode = $request->input('barcode');

    if (!$barcode) {
        return response()->json(['message' => 'Barcode is required'], 400);
    }

    // Fetch product directly from products table
    $product = Product::where('barcode', $request->input('barcode'))->first();

if ($product) {
    return response()->json(['data' => [$product]]); // Wrap in array
} else {
    return response()->json(['data' => []], 404);
}

}

}
