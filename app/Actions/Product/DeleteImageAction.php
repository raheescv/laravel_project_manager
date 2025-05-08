<?php

namespace App\Actions\Product;

use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

class DeleteImageAction
{
    public function execute($id)
    {
        try {
            $model = ProductImage::find($id);
            if (! $model) {
                throw new \Exception("ProductImage not found with the specified ID: $id.", 1);
            }
            $storagePath = parse_url($model->path, PHP_URL_PATH);
            $relativePath = str_replace('/storage/', '', $storagePath);
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
            if ($model->product->thumbnail == $model->path) {
                $model->product->update(['thumbnail' => null]);
            }
            if (! $model->delete()) {
                throw new \Exception('Oops! Something went wrong while deleting the ProductImage. Please try again.', 1);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Update ProductImage';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
