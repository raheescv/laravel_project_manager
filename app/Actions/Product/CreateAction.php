<?php

namespace App\Actions\Product;

use App\Models\Category;
use App\Models\Department;
use App\Models\Product;

class CreateAction
{
    public function execute($data)
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

            validationHelper(Product::rules(), $data);

            $model = Product::create($data);

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
