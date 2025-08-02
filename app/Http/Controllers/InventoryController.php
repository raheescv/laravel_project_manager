<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }

    public function search()
    {
        return view('inventory.product-search');
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
}
