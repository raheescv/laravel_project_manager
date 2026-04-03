<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        return view('product.index');
    }

    public function page($id = null)
    {
        $type = 'product';

        return view('product.page', compact('type', 'id'));
    }

    public function import()
    {
        return view('product.import');
    }

    public function gallery()
    {
        return view('product.gallery');
    }

    public function get(Request $request)
    {
        $list = (new Product())->getDropDownList($request->all());

        return response()->json($list);
    }

    public function downloadDocument(Product $product)
    {
        if (! $product->document_file) {
            abort(404, 'No document found for this product.');
        }

        $relativePath = str_replace('/storage/', '', parse_url($product->document_file, PHP_URL_PATH));

        if (! Storage::disk('public')->exists($relativePath)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('public')->download($relativePath, $product->document_file_name);
    }

    public function list(Request $request)
    {
        $query = Product::with(['unit', 'mainCategory'])
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', '=', $search)
                        ->orWhere('barcode', '=', $search);
                });
            })
            ->when($request->type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($request->main_category_id, function ($query, $main_category_id) {
                return $query->where('main_category_id', $main_category_id);
            })
            // ->where('name', 1)
            ->limit(2)
            ->active();

        $products = $request->per_page ? $query->paginate($request->per_page) : $query->get();

        return ProductResource::collection($products);
    }
}
