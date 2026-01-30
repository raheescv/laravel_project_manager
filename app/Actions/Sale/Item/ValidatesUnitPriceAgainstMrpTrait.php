<?php

namespace App\Actions\Sale\Item;

use App\Models\Configuration;
use App\Models\Product;
use App\Models\ProductUnit;
use Exception;

trait ValidatesUnitPriceAgainstMrpTrait
{
    protected function validateUnitPriceAgainstMrp(array $data): void
    {
        $shouldValidate = $this->shouldValidateUnitPriceAgainstMrp();

        if (! $shouldValidate) {
            return;
        }

        $product = Product::find($data['product_id']);
        $productMrp = $this->calculateProductMrp($product, $data['unit_id']);

        if ($this->isUnitPriceExceedingMrp($product, $data['unit_price'], $productMrp)) {
            throw new Exception('Unit price cannot be greater than MRP.', 1);
        }
    }

    protected function shouldValidateUnitPriceAgainstMrp(): bool
    {
        $configValue = Configuration::where('key', 'validate_unit_price_against_mrp')
            ->value('value') ?? 'yes';

        return $configValue === 'yes';
    }

    protected function calculateProductMrp(Product $product, int $unitId): float
    {
        $productMrp = $product->mrp;
        if ($product->unit_id != $unitId) {

            $productUnit = $this->findProductUnit($product, $unitId);

            if ($productUnit) {
                $productMrp = $productMrp * $productUnit->conversion_factor;
            }
        }

        return $productMrp;
    }

    protected function isUnitPriceExceedingMrp(Product $product, float $unitPrice, float $productMrp): bool
    {
        return $product->type === 'product' && $unitPrice > $productMrp;
    }

    /**
     * Find the ProductUnit for MRP calculation.
     * Override this method in the class using the trait if different logic is needed.
     */
    protected function findProductUnit(Product $product, int $unitId): ?ProductUnit
    {
        return ProductUnit::where('product_id', $product->id)
            ->where('sub_unit_id', $unitId)
            ->first();
    }
}
