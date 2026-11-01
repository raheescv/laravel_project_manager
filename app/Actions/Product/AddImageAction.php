<?php

namespace App\Actions\Product;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;

/**
 * Store one uploaded photo against a product — the single place the web form
 * and the mobile app write a normal (non-360°) product image.
 */
class AddImageAction
{
    public function execute(Product $product, UploadedFile $file): ProductImage
    {
        return $product->images()->create([
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'type' => $file->getClientOriginalExtension(),
            'method' => 'normal',
            'path' => url('storage/'.$file->store('products/'.$product->id, 'public')),
        ]);
    }
}
