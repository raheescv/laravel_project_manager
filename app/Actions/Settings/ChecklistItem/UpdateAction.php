<?php

namespace App\Actions\Settings\ChecklistItem;

use App\Models\Checklist;

class UpdateAction
{
    public function execute($data, $id)
    {
        try {
            $model = Checklist::find($id);
            if (! $model) {
                throw new \Exception("Checklist Item not found with the specified ID: $id.", 1);
            }
            $data['name'] = trim($data['name']);
            $data['property_type_id'] = $data['property_type_id'] ?: null;
            $category = $data['category'] ?? null;
            $propertyTypeId = $data['property_type_id'];
            $duplicate = Checklist::where('id', '!=', $id)
                ->where('name', $data['name'])
                ->when($category !== null && $category !== '', fn ($q) => $q->where('category', $category), fn ($q) => $q->whereNull('category'))
                ->when($propertyTypeId, fn ($q) => $q->where('property_type_id', $propertyTypeId), fn ($q) => $q->whereNull('property_type_id'))
                ->exists();
            if ($duplicate) {
                throw new \Exception('A checklist item with this name already exists for this category and property type.', 1);
            }
            validationHelper(Checklist::rules($id), $data);
            $model->update($data);
            $return['success'] = true;
            $return['message'] = 'Successfully Updated Checklist Item';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
