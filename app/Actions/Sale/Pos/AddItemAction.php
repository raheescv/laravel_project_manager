<?php

namespace App\Actions\Sale\Pos;

use App\Models\Configuration;
use App\Models\Inventory;
use App\Models\ProductUnit;
use App\Models\UniversalUnitConversion;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AddItemAction
{
    public function execute(int $inventoryId, int $employeeId, ?string $saleType = 'normal', ?int $unitId = null)
    {
        try {
            $inventory = $this->loadInventory($inventoryId);
            $saleType = $saleType ?? 'normal';

            $baseUnit = $this->getBaseUnit($inventory);
            $pricing = $this->basePricing($inventory, $saleType);
            $baseUnitPrice = $pricing['unit_price'];

            $unitInfo = $this->getUnitInfo($inventory, $unitId);
            $unitPrice = $baseUnitPrice * $unitInfo['conversion_factor'];
            $offerUnitDiscount = $pricing['offer_discount'] * $unitInfo['conversion_factor'];

            $taxRate = $inventory->product->tax ?? 0;
            $quantity = $this->getDefaultQuantity();

            $totals = $this->calculateTotals($unitPrice, $quantity, $taxRate, round($offerUnitDiscount * $quantity, 2));
            $employeeName = $this->getEmployeeName($employeeId);

            $item = $this->buildItemData(
                $inventory,
                $employeeId,
                $employeeName,
                $unitInfo,
                $baseUnit,
                $baseUnitPrice,
                $unitPrice,
                $taxRate,
                $quantity,
                $totals
            ) + [
                'base_offer_discount' => $pricing['offer_discount'],
                'offer_unit_discount' => $offerUnitDiscount,
                'offer_price' => $unitPrice - $offerUnitDiscount,
                'offer_label' => $pricing['offer_discount'] > 0 ? (priceTypes()[$saleType] ?? 'Offer') : null,
            ];

            return [
                'success' => true,
                'data' => $item,
            ];
        } catch (\Exception $e) {
            Log::error('Error adding item: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to add item',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function loadInventory(int $inventoryId): Inventory
    {
        return Inventory::with(['product.unit', 'product.units.subUnit'])->findOrFail($inventoryId);
    }

    /**
     * Base-unit pricing for the sale type. A sale-type price below MRP (an
     * offer) is shown as MRP less a per-unit discount, so the line keeps the
     * real price and the discount grows with the quantity.
     *
     * @return array{unit_price: float, offer_discount: float}
     */
    private function basePricing(Inventory $inventory, string $saleType): array
    {
        $mrp = (float) $inventory->product->mrp;
        $saleTypePrice = (float) $inventory->product->saleTypePrice($saleType);

        if ($saleTypePrice < $mrp) {
            return ['unit_price' => $mrp, 'offer_discount' => round($mrp - $saleTypePrice, 4)];
        }

        return ['unit_price' => $saleTypePrice, 'offer_discount' => 0.0];
    }

    private function getBaseUnit(Inventory $inventory): array
    {
        return [
            'id' => $inventory->product->unit_id,
            'name' => $inventory->product->unit->name ?? '',
            'conversion_factor' => 1,
        ];
    }

    private function getUnitInfo(Inventory $inventory, ?int $unitId): array
    {
        $baseUnitId = $inventory->product->unit_id;
        $selectedUnitId = $unitId ?? $baseUnitId;

        $unitInfo = [
            'unit_id' => $selectedUnitId,
            'unit_name' => $inventory->product->unit->name ?? '',
            'conversion_factor' => 1,
            'barcode' => $inventory->product->barcode,
        ];

        if ($selectedUnitId != $baseUnitId) {
            $productUnit = ProductUnit::where('product_id', $inventory->product_id)
                ->where('sub_unit_id', $selectedUnitId)
                ->with('subUnit')
                ->first();

            if ($productUnit) {
                $unitInfo['conversion_factor'] = $productUnit->conversion_factor;
                $unitInfo['unit_name'] = $productUnit->subUnit->name ?? '';
                $unitInfo['barcode'] = $productUnit->barcode ?? $unitInfo['barcode'];
            } else {
                $universal = UniversalUnitConversion::with('subUnit')
                    ->where('base_unit_id', $baseUnitId)
                    ->where('sub_unit_id', $selectedUnitId)
                    ->first();
                if ($universal && $universal->subUnit) {
                    $unitInfo['conversion_factor'] = $universal->conversion_factor;
                    $unitInfo['unit_name'] = $universal->subUnit->name ?? '';
                }
            }
        }

        return $unitInfo;
    }

    private function getDefaultQuantity(): float
    {
        return (float) (Configuration::where('key', 'default_quantity')->value('value') ?? '0.001');
    }

    private function calculateTotals(float $unitPrice, float $quantity, float $taxRate, float $discount = 0): array
    {
        $grossAmount = $unitPrice * $quantity;
        $netAmount = $grossAmount - $discount;
        $taxAmount = $netAmount * ($taxRate / 100);
        $total = $netAmount + $taxAmount;

        return [
            'gross_amount' => $grossAmount,
            'discount' => $discount,
            'net_amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    private function getEmployeeName(int $employeeId): string
    {
        return User::find($employeeId)->name ?? '';
    }

    private function buildItemData(
        Inventory $inventory,
        int $employeeId,
        string $employeeName,
        array $unitInfo,
        array $baseUnit,
        float $baseUnitPrice,
        float $unitPrice,
        float $taxRate,
        float $quantity,
        array $totals
    ): array {
        return [
            'id' => null,
            'inventory_id' => $inventory->id,
            'product_id' => $inventory->product_id,
            'employee_id' => $employeeId,
            'name' => $inventory->product->name,
            'barcode' => $unitInfo['barcode'],
            'size' => $inventory->product->size,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'base_unit_price' => $baseUnitPrice,
            'tax' => $taxRate,
            'discount' => $totals['discount'],
            'gross_amount' => $totals['gross_amount'],
            'net_amount' => $totals['net_amount'],
            'tax_amount' => $totals['tax_amount'],
            'total' => $totals['total'],
            'employee_name' => $employeeName,
            'unit_id' => $unitInfo['unit_id'],
            'unit_name' => $unitInfo['unit_name'],
            'conversion_factor' => $unitInfo['conversion_factor'],
            'base_unit' => $baseUnit,
            'units' => $this->buildUnitsArray($inventory),
        ];
    }

    private function buildUnitsArray(Inventory $inventory): array
    {
        return $inventory->product->getResolvedUnits();
    }
}
