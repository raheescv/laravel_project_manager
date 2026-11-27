<?php

namespace App\Actions\Product\Variant;

use App\Actions\Product\CreateAction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Makes design (flavour, model…) / size / colour variants of a product. Every variant is a full product
 * (own barcode, code, cost, MRP and stock) whose parent_id is the family head,
 * so sales, purchases, transfers and reports treat it like any other item.
 *
 * The whole batch is checked before anything is written, then each variant goes
 * through the normal product CreateAction inside one transaction: one bad row
 * means nothing is created.
 */
class CreateVariantsAction
{
    /**
     * @param  array<int, array{variant_value?: ?string, size?: ?string, color?: ?string, barcode_number?: ?string, cost?: mixed, mrp?: mixed, opening_stock?: mixed}>  $variants
     * @return array{success: bool, message: string, data?: array<int, Product>}
     */
    public function execute(int $headId, array $variants, int $userId): array
    {
        try {
            $head = Product::find($headId);
            if (! $head) {
                throw new \Exception("Product not found with the specified ID: {$headId}.");
            }
            if ($head->parent_id) {
                throw new \Exception("{$head->name} is itself a variant. Add variants from the main product instead.");
            }
            if ($head->type !== 'product') {
                throw new \Exception('Only products can have variants.');
            }
            if (! $variants) {
                throw new \Exception('Pick at least one variant to create.');
            }

            $rows = $this->checkedRows($head, $variants);

            $created = DB::transaction(function () use ($head, $rows, $userId): array {
                $created = [];
                foreach ($rows as $row) {
                    $response = (new CreateAction())->execute($this->productData($head, $row), $userId);
                    if (! $response['success']) {
                        throw new \Exception(Product::variantLabel($row['size'], $row['color'], $row['variant_value']).': '.$response['message']);
                    }
                    $created[] = $response['data'];
                }

                return $created;
            });

            $count = count($created);

            return [
                'success' => true,
                'message' => "Created {$count} variant".($count === 1 ? '' : 's')." of {$head->name}",
                'data' => $created,
            ];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Trim the rows and reject the batch, with every problem listed, when a row
     * has neither a design nor a size, a design + size + colour repeats (in the batch or the family), or a
     * barcode repeats inside the batch. Barcodes already used elsewhere are caught
     * by Product::rules() when each row is saved.
     *
     * @return array<int, array{variant_value: ?string, size: ?string, color: ?string, barcode_number: ?string, cost: float, mrp: float, opening_stock: float}>
     */
    private function checkedRows(Product $head, array $variants): array
    {
        $rows = [];
        $errors = [];
        $seenCombos = [];
        $seenBarcodes = [];

        foreach (array_values($variants) as $index => $variant) {
            $design = trim((string) ($variant['variant_value'] ?? ''));
            $size = trim((string) ($variant['size'] ?? ''));
            $color = trim((string) ($variant['color'] ?? ''));
            $barcode = trim((string) ($variant['barcode_number'] ?? ''));
            $label = $size === '' && $design === '' ? 'Row '.($index + 1) : Product::variantLabel($size, $color, $design);

            if ($size === '' && $design === '') {
                $errors[] = "{$label}: a ".mb_strtolower($head->optionName()).' or a size is required.';

                continue;
            }

            $combo = Product::normaliseVariantValue($design).'|'.Product::normaliseVariantValue($size).'|'.Product::normaliseVariantValue($color);
            if (isset($seenCombos[$combo])) {
                $errors[] = "{$label} is listed twice.";
            } elseif (Product::familyCombinationTaken($head->id, $size, $color, 0, $design)) {
                $errors[] = "{$head->name} already has a {$label} variant.";
            }
            $seenCombos[$combo] = true;

            if ($barcode !== '') {
                if (isset($seenBarcodes[$barcode])) {
                    $errors[] = "Barcode {$barcode} is used by {$seenBarcodes[$barcode]} and {$label}.";
                }
                $seenBarcodes[$barcode] = $label;
            }

            foreach (['cost', 'mrp', 'opening_stock'] as $field) {
                $value = $variant[$field] ?? null;
                if ($value !== null && $value !== '' && (! is_numeric($value) || (float) $value < 0)) {
                    $errors[] = "{$label}: ".str_replace('_', ' ', $field).' must be a number of 0 or more.';
                }
            }

            $rows[] = [
                'variant_value' => $design === '' ? null : $design,
                'size' => $size === '' ? null : $size,
                'color' => $color === '' ? null : $color,
                'barcode_number' => $barcode === '' ? null : $barcode,
                'cost' => (float) (($variant['cost'] ?? '') === '' ? $head->cost : $variant['cost']),
                'mrp' => (float) (($variant['mrp'] ?? '') === '' ? $head->mrp : $variant['mrp']),
                'opening_stock' => (float) (($variant['opening_stock'] ?? '') === '' ? 0 : $variant['opening_stock']),
            ];
        }

        if ($errors) {
            throw new \Exception(implode(' ', $errors));
        }

        return $rows;
    }

    /** The head's shared fields plus the row's own design, size, colour, barcode, prices and stock. */
    private function productData(Product $head, array $row): array
    {
        return array_merge($head->only(Product::VARIANT_SHARED_FIELDS), [
            'parent_id' => $head->id,
            'variant_value' => $row['variant_value'],
            'size' => $row['size'],
            'color' => $row['color'],
            'barcode_number' => $row['barcode_number'],
            'cost' => $row['cost'],
            'mrp' => $row['mrp'],
            'opening_stock' => $row['opening_stock'],
            'thumbnail' => $head->thumbnail,
            'is_favorite' => false,
            'status' => $head->status,
        ]);
    }
}
