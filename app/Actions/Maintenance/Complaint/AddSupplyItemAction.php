<?php

namespace App\Actions\Maintenance\Complaint;

use App\Actions\Maintenance\Complaint\Concerns\ManagesSupplyRequest;
use App\Models\MaintenanceComplaint;
use App\Models\Product;
use App\Models\SupplyRequestItem;
use Illuminate\Support\Facades\DB;

/**
 * Add a supply item to a complaint's (lazily-created) supply request. Mirrors
 * Complaint::addCart, including the barcode lookup that resolves and defaults a
 * product (updated('item.barcode')). `unit_price` defaults to product.cost;
 * `total` is a stored column (quantity × unit_price). Shared by the web page
 * and the mobile technician API.
 */
class AddSupplyItemAction
{
    use ManagesSupplyRequest;

    /**
     * @param  array<string, mixed>  $data  branch_id, product_id|barcode, mode, quantity, unit_price?, remarks
     */
    public function execute($complaintId, array $data, $userId)
    {
        try {
            $mc = MaintenanceComplaint::with('maintenance')->find($complaintId);
            if (! $mc) {
                throw new \Exception("Maintenance Complaint not found with the specified ID: $complaintId.", 1);
            }

            // Barcode flow: resolve the product and default its price (web parity).
            $product = null;
            if (empty($data['product_id']) && ! empty($data['barcode'])) {
                $product = Product::where('barcode', $data['barcode'])->first();
                if (! $product) {
                    throw new \Exception('No product matches that barcode.', 1);
                }
                $data['product_id'] = $product->id;
            }

            if (empty($data['branch_id'])) {
                throw new \Exception('Please select a store.', 1);
            }
            if (empty($data['product_id'])) {
                throw new \Exception('Please select an asset/product.', 1);
            }

            $product ??= Product::find($data['product_id']);
            $quantity = (float) ($data['quantity'] ?? 1);
            // Price defaults to product cost when the caller omits it.
            $unitPrice = array_key_exists('unit_price', $data) && $data['unit_price'] !== null
                ? (float) $data['unit_price']
                : (float) ($product?->cost ?? 0);

            $item = DB::transaction(function () use ($mc, $data, $quantity, $unitPrice, $userId) {
                $sr = $this->getOrCreateSupplyRequest($mc, $userId);

                $item = SupplyRequestItem::create([
                    'supply_request_id' => $sr->id,
                    'product_id' => $data['product_id'],
                    'branch_id' => $data['branch_id'],
                    'mode' => $data['mode'] ?? 'New',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'remarks' => $data['remarks'] ?? '',
                ]);

                $this->recalculateSupplyTotals($sr);

                return $item;
            });

            $return['success'] = true;
            $return['message'] = 'Successfully added to cart';
            $return['data'] = $item;
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = $th->getMessage();
        }

        return $return;
    }
}
