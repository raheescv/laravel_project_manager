<?php

namespace App\Actions\Sale\Pos;

use App\Models\Inventory;
use App\Models\ProductUnit;
use Illuminate\Support\Facades\Log;

class GetProductByBarcodeAction
{
    public function execute(string $barcode, ?string $saleType = 'normal', ?int $branchId = null)
    {
        try {
            $branchId = $branchId ?? session('branch_id');

            // First try to find by product barcode
            $inventory = Inventory::with(['product.unit', 'product.units.subUnit'])
                ->whereNull('inventories.employee_id')
                ->whereHas('product', function ($q) use ($barcode): void {
                    $q->where('barcode', $barcode)->where('status', 'active');
                })
                ->where('inventories.branch_id', $branchId)
                ->first();

            $productUnit = null;
            $selectedUnitId = null;
            $selectedUnitName = null;
            $selectedConversionFactor = 1;

            // If not found, try to find by ProductUnit barcode
            if (! $inventory) {
                $productUnit = ProductUnit::with(['product', 'subUnit'])
                    ->where('barcode', $barcode)
                    ->whereHas('product', function ($q): void {
                        $q->where('status', 'active');
                    })
                    ->first();

                if ($productUnit) {
                    // Find inventory for the product from ProductUnit
                    $inventory = Inventory::with(['product.unit', 'product.units.subUnit'])
                        ->whereNull('inventories.employee_id')
                        ->where('inventories.product_id', $productUnit->product_id)
                        ->where('inventories.branch_id', $branchId)
                        ->first();

                    if ($inventory) {
                        // Set the unit information from ProductUnit
                        $selectedUnitId = $productUnit->sub_unit_id;
                        $selectedUnitName = $productUnit->subUnit->name ?? '';
                        $selectedConversionFactor = $productUnit->conversion_factor;
                    }
                }
            }

            if (! $inventory) {
                return [
                    'success' => false,
                    'data' => null,
                    'status' => 404,
                ];
            }

            $saleType = $saleType ?? 'normal';
            // Base price is always based on product's unit_id (base unit)
            $basePrice = $inventory->product->saleTypePrice($saleType);
            // Adjust price if ProductUnit is found (multiply by conversion factor)
            $price = $basePrice * $selectedConversionFactor;

            // Get product image URL
            $imageUrl = cache('logo');
            if ($inventory->product->thumbnail) {
                $imageUrl = $inventory->product->thumbnail;
            }

            // Use ProductUnit barcode if found, otherwise use product barcode
            $barcode = $productUnit ? $productUnit->barcode : $inventory->product->barcode;

            $data = [
                'id' => $inventory->id,
                'name' => $inventory->product->name,
                'type' => $inventory->product->type,
                'barcode' => $barcode,
                'mrp' => $price,
                'stock' => $inventory->quantity ?? 0,
                'category_id' => $inventory->category_id,
                'product_id' => $inventory->product_id,
                'branch_id' => $inventory->branch_id,
                'image' => $imageUrl,
                'unit_id' => $selectedUnitId ?? $inventory->product->unit_id,
                'unit_name' => $selectedUnitName ?? ($inventory->product->unit->name ?? ''),
                'conversion_factor' => $selectedConversionFactor,
                'units' => collect([
                    [
                        'id' => $inventory->product->unit_id,
                        'name' => $inventory->product->unit->name ?? '',
                        'conversion_factor' => 1,
                    ],
                ])->concat($inventory->product->units->map(function ($pu) {
                    return [
                        'id' => $pu->sub_unit_id,
                        'name' => $pu->subUnit->name ?? '',
                        'conversion_factor' => $pu->conversion_factor,
                    ];
                })),
            ];

            return [
                'success' => true,
                'data' => $data,
                'status' => 200,
            ];
        } catch (\Exception $e) {
            Log::error('Error finding product by barcode: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }
}
