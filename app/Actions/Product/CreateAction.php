<?php

namespace App\Actions\Product;

use App\Actions\Asset\GenerateDepreciationScheduleAction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Product;

class CreateAction
{
    public function execute($data, $user_id)
    {
        try {
            if (isset($data['department_id']) && is_string($data['department_id']) && str_contains($data['department_id'], 'add ')) {
                $name = str_replace('add ', '', $data['department_id']);
                $data['department_id'] = Department::selfCreate($name);
            }

            if (isset($data['brand_id']) && is_string($data['brand_id']) && str_contains($data['brand_id'], 'add ')) {
                $name = str_replace('add ', '', $data['brand_id']);
                $data['brand_id'] = Brand::selfCreate($name);
            }

            if (isset($data['main_category_id']) && is_string($data['main_category_id']) && str_contains($data['main_category_id'], 'add ')) {
                $name = str_replace('add ', '', $data['main_category_id']);
                $departmentData['name'] = $name;
                $data['main_category_id'] = Category::selfCreate($departmentData);
            }

            if (isset($data['sub_category_id']) && is_string($data['sub_category_id']) && str_contains($data['sub_category_id'], 'add ')) {
                $name = str_replace('add ', '', $data['sub_category_id']);
                $departmentData['parent_id'] = $data['main_category_id'];
                $departmentData['name'] = $name;
                $data['sub_category_id'] = Category::selfCreate($departmentData);
            }

            if ($data['type'] == 'service') {
                $data['cost'] = $data['mrp'];
            }

            if ($data['type'] == 'service') {
                $data['barcode_number'] = generateBarcode();
            }
            if ($data['type'] == 'product' && empty($data['barcode_number'])) {
                $data['barcode_number'] = generateBarcode();
            }
            if (! isset($data['code']) || empty($data['code'])) {
                $data['code'] = Product::generateUniqueCode();
            }
            validationHelper(Product::rules($data), $data);
            $user_id = $data['created_by'] = $data['updated_by'] = $user_id;
            $trashedExists = $this->trashedTwin($data);
            if ($trashedExists) {
                $trashedExists->restore();
                $trashedExists->update($data);
                $model = $trashedExists;
            } else {
                $model = Product::create($data);
            }
            $isBarcodeSyncEnabled = Configuration::where('key', 'sync_barcode_to_code')->value('value') === 'yes';
            if ($isBarcodeSyncEnabled && $data['type'] !== 'service') {
                $model->refresh();
                $model->update(['code' => $model->barcode]);
            }
            $openingStock = $data['type'] === 'service' ? 0 : max(0, (float) ($data['opening_stock'] ?? 0));
            Inventory::selfCreateByProduct($model, $user_id, $openingStock, session('branch_id', 1));
            if ($data['type'] === 'asset') {
                $scheduleResponse = (new GenerateDepreciationScheduleAction())->execute($model->fresh(), $user_id);
                if (! $scheduleResponse['success']) {
                    throw new \Exception($scheduleResponse['message']);
                }
            }
            // Handle document file upload
            if (isset($data['document_file_upload']) && $data['document_file_upload']) {
                $file = $data['document_file_upload'];
                $storedPath = $file->store('products/'.$model->id.'/documents', 'public');
                $model->refresh();
                $model->update([
                    'document_file' => url('storage/'.$storedPath),
                    'document_file_name' => $file->getClientOriginalName(),
                ]);
            }

            if (isset($data['images'])) {
                foreach ($data['images'] as $file) {
                    (new AddImageAction())->execute($model, $file);
                }
                $path = $model->images()->normal()->first()?->path;
                $model->update(['thumbnail' => $path]);
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
            $return['message'] = 'Successfully Created '.str($model->type)->replace('_', ' ')->title();
            $return['data'] = $model;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }

    /**
     * A soft-deleted product this create should bring back instead of inserting.
     * Variants of one family share a name, so a name alone would revive (and
     * overwrite) a deleted sibling: a variant only matches the same family, size
     * and colour; anything else only matches a deleted head/standalone product.
     */
    private function trashedTwin(array $data): ?Product
    {
        $query = Product::onlyTrashed()->where('type', $data['type'] ?? 'product');

        if (! empty($data['parent_id'])) {
            return $query->where('parent_id', $data['parent_id'])
                ->whereRaw("LOWER(TRIM(COALESCE(size, ''))) = ?", [Product::normaliseVariantValue($data['size'] ?? null)])
                ->whereRaw("LOWER(TRIM(COALESCE(color, ''))) = ?", [Product::normaliseVariantValue($data['color'] ?? null)])
                ->first();
        }

        return $query->whereNull('parent_id')->firstWhere('name', $data['name']);
    }
}
