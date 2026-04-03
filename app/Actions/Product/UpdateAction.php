<?php

namespace App\Actions\Product;

use App\Models\Category;
use App\Models\Department;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class UpdateAction
{
    public function execute($data, $id, $user_id)
    {
        try {
            /** @var Product|null $model */
            $model = Product::find($id);
            if (! $model) {
                throw new \Exception("Product not found with the specified ID: $id.", 1);
            }

            if (str_contains($data['department_id'], 'add ')) {
                $name = str_replace('add ', '', $data['department_id']);
                $data['department_id'] = Department::selfCreate($name);
            }

            if (str_contains($data['main_category_id'], 'add ')) {
                $name = str_replace('add ', '', $data['main_category_id']);
                $departmentData['name'] = $name;
                $data['main_category_id'] = Category::selfCreate($departmentData);
            }

            if (str_contains($data['sub_category_id'], 'add ')) {
                $name = str_replace('add ', '', $data['sub_category_id']);
                $departmentData['parent_id'] = $data['main_category_id'];
                $departmentData['name'] = $name;
                $data['sub_category_id'] = Category::selfCreate($departmentData);
            }

            if ($data['type'] == 'service') {
                $data['cost'] = $data['mrp'];
            }
            if ($data['type'] == 'service') {
                unset($data['barcode_number']);
            }
            validationHelper(Product::rules($data, $id), $data);
            $data['updated_by'] = $user_id;
            $model->update($data);

            if ($data['type'] == 'product') {
                $model->inventories()->update(['barcode_number' => $data['barcode_number']]);
            }

            // Handle document file upload
            if (isset($data['document_file_upload']) && $data['document_file_upload']) {
                $file = $data['document_file_upload'];
                // Delete old document if exists
                if ($model->document_file) {
                    $oldPath = str_replace('/storage/', '', parse_url($model->document_file, PHP_URL_PATH));
                    Storage::disk('public')->delete($oldPath);
                }
                $storedPath = $file->store('products/'.$model->id.'/documents', 'public');
                $model->update([
                    'document_file' => url('storage/'.$storedPath),
                    'document_file_name' => $file->getClientOriginalName(),
                ]);
            }

            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $file) {
                    $imageData = [
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'type' => $file->getClientOriginalExtension(),
                        'method' => 'normal',
                        'path' => url('storage/'.$file->store('products/'.$model->id, 'public')),
                    ];
                    $model->images()->create($imageData);
                }
                if (! $model->thumbnail) {
                    $path = $model->images()->normal()->first()?->path;
                    $model->update(['thumbnail' => $path]);
                }
            }

            // Handle 360-degree images
            if (isset($data['angles_360']) && is_array($data['angles_360'])) {
                foreach ($data['angles_360'] as $index => $file) {
                    $imageData = [
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'type' => $file->getClientOriginalExtension(),
                        'method' => 'angle',
                        'path' => url('storage/'.$file->store('products/'.$model->id.'/360', 'public')),
                        'degree' => $data['degree'][$index] ?? $index * (360 / count($data['angles_360'])),
                        'sort_order' => $index,
                    ];
                    $model->images()->create($imageData);
                }
            }

            $return['success'] = true;
            $return['message'] = 'Successfully Update Product';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
