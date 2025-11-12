<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicScanController extends Controller
{
     public function home()
    {
        return Inertia::render('Home');
    }
    public function index()
    {
        // Render the React/Inertia page
         Inertia::setRootView('app-react');
        return Inertia::render('Scan/Scanner');
    }

   public function search(Request $request)
{
    $barcode = $request->input('barcode');
    \Log::info('Received barcode: '.$barcode);

    $product = Product::where('barcode', $barcode)->first();

    if (!$product) {
        \Log::info('Product not found for barcode: '.$barcode);
        return response()->json(['message' => 'No product found'], 404);
    }

    return response()->json([
        'name' => $product->name,
        'code' => $product->code,
        'barcode' => $product->barcode,
    ]);
}


}