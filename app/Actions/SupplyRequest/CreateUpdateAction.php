<?php

namespace App\Actions\SupplyRequest;

use App\Models\SupplyRequest;
use App\Models\SupplyRequestImage;
use App\Models\SupplyRequestItem;
use App\Models\SupplyRequestNote;

class CreateUpdateAction
{
    public function execute(array $data, array $items, array $images, array $notes, int $userId, ?int $id = null): array
    {
        try {
            $isCreate = ! $id;

            $data['updated_by'] = $userId;

            if ($id) {
                $model = SupplyRequest::findOrFail($id);
                $model->update($data);
            } else {
                $data['created_by'] = $userId;
                $data['tenant_id'] = session('tenant_id');
                $data['branch_id'] = session('branch_id', 1);
                $data['order_no'] = time();
                $model = SupplyRequest::create($data);
            }

            // Save items
            foreach ($items as $itemData) {
                if (isset($itemData['id'])) {
                    $item = SupplyRequestItem::find($itemData['id']);
                    if ($item) {
                        $item->update([
                            'branch_id' => $itemData['branch_id'],
                            'product_id' => $itemData['product_id'],
                            'mode' => $itemData['mode'],
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $itemData['unit_price'],
                            'remarks' => $itemData['remarks'] ?? null,
                        ]);
                    }
                } else {
                    SupplyRequestItem::create([
                        'supply_request_id' => $model->id,
                        'branch_id' => $itemData['branch_id'],
                        'product_id' => $itemData['product_id'],
                        'mode' => $itemData['mode'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'remarks' => $itemData['remarks'] ?? null,
                    ]);
                }
            }

            // Save images
            foreach ($images as $file) {
                $imageModel = new SupplyRequestImage();
                $result = $imageModel->storeFile($file, $model->id);
                if ($result['success']) {
                    SupplyRequestImage::create([
                        'supply_request_id' => $model->id,
                        'name' => $result['fileName'],
                        'path' => $result['path'],
                        'type' => $result['type'],
                    ]);
                }
            }

            // Save notes
            foreach ($notes as $noteData) {
                if (! isset($noteData['id'])) {
                    SupplyRequestNote::create([
                        'supply_request_id' => $model->id,
                        'note' => $noteData['note'],
                        'created_by' => $noteData['created_by'],
                    ]);
                }
            }

            // Update totals
            $model->update([
                'total' => $data['total'],
                'other_charges' => $data['other_charges'] ?? 0,
                'grand_total' => $data['grand_total'],
            ]);

            return [
                'success' => true,
                'data' => $model,
                'is_create' => $isCreate,
                'message' => $isCreate ? 'Successfully created' : 'Successfully updated',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
