<?php

namespace App\Http\Controllers;

use App\Models\Product;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index');
    }

    public function view($product_id)
    {
        $product = Product::find($product_id);

        return view('inventory.view', compact('product_id', 'product'));
    }
}
