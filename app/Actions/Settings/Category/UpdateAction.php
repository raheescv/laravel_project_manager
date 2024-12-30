<?php

namespace App\Actions\Settings\Category;

use App\Models\Category;

class UpdateAction
{
    public $data;

    public function execute($data, $id)
    {
        try {
            $data['name'] = trim($data['name']);
            $this->data = $data;
            $model = Category::find($id);
            if (! $model) {
                throw new \Exception("Resource not found with the specified ID: $id.", 1);
            }
            $this->parentCreate();
            validationHelper(Category::rules($id), $this->data);
            $model->update($this->data);
            $return['success'] = true;
            $return['message'] = 'Successfully Update Category';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    private function parentCreate()
    {
        if (str_contains($this->data['parent_id'], 'add ')) {
            $parent = str_replace('add ', '', $this->data['parent_id']);
            $this->data['parent_id'] = Category::parentCreate($parent);
        }
    }
}
