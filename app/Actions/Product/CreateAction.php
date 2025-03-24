<?php

namespace App\Actions\Product;

use App\Models\Category;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Product;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
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

            validationHelper(Product::rules(), $data);
            $user_id = $data['created_by'] = $data['updated_by'] = $user_id;
            $trashedExists = Product::withTrashed()->firstWhere('name', $data['name']);
            if ($trashedExists) {
                $trashedExists->restore();
                $trashedExists->update($data);
                $model = $trashedExists;
            } else {
                $model = Product::create($data);
            }
            if ('inventory') {
                Inventory::selfCreateByProduct($model, $user_id, $quantity = 0);
            }
            if (isset($data['images'])) {
                foreach ($data['images'] as $file) {
                    $imageData = [
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'type' => $file->getClientOriginalExtension(),
                        'path' => url('storage/'.$file->store('products/'.$model->id, 'public')),
                    ];
                    $model->images()->create($imageData);
                }
                $path = $model->images()?->first()?->path;
                $model->update(['thumbnail' => $path]);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Product';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
