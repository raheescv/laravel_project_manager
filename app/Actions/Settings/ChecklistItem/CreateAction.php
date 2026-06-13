<?php

namespace App\Actions\Settings\ChecklistItem;

use App\Models\Checklist;

class CreateAction
{
    public function execute($data)
    {
        try {
            $data['name'] = trim($data['name']);
            $data['property_type_id'] = $data['property_type_id'] ?: null;
            validationHelper(Checklist::rules(), $data, 'Checklist Item');
            $category = $data['category'] ?? null;
            $propertyTypeId = $data['property_type_id'];
            $exists = Checklist::withTrashed()
                ->where('name', $data['name'])
                ->when($category !== null && $category !== '', fn ($q) => $q->where('category', $category), fn ($q) => $q->whereNull('category'))
                ->when($propertyTypeId, fn ($q) => $q->where('property_type_id', $propertyTypeId), fn ($q) => $q->whereNull('property_type_id'))
                ->first();
            if ($exists) {
                $exists->restore();
                $exists->update(array_merge(['is_active' => true], $data));
                $model = $exists;
            } else {
                $model = Checklist::create($data);
            }
            $return['success'] = true;
            $return['message'] = 'Successfully Created Checklist Item';
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
