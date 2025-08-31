<?php

namespace App\Actions\Settings\Category;

use App\Models\Category;

class CreateAction
{
    public $data;

    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            $this->data = $data;
            $this->parentCreate();
            validationHelper(Category::rules(), $this->data, 'Category');
            $model = Category::create($this->data);
            $return['success'] = true;
            $return['message'] = 'Successfully Created Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function parentCreate()
    {
        if (isset($this->data['parent_id'])) {
            if (str_contains($this->data['parent_id'], 'add ')) {
                $parent = str_replace('add ', '', $this->data['parent_id']);
                $this->data['parent_id'] = Category::parentCreate($parent);
            }
        }
    }
}
