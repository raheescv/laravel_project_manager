<?php

namespace App\Support;

use App\Models\Configuration;
use App\Models\Inventory;
use App\Models\Product;

/**
 * Read helpers shared by every barcode label view (preview, single print and
 * cart print) so the same settings always render the same label.
 */
class BarcodeLabel
{
    public static function type(array $settings): string
    {
        return BarcodeTemplateConfiguration::resolveType($settings);
    }

    /**
     * The blade folder under resources/views/inventory/label-types for a type.
     */
    public static function viewPath(array $settings, string $view): string
    {
        $folder = str_replace('_', '-', self::type($settings));

        return "inventory.label-types.{$folder}.{$view}";
    }

    /**
     * Text wing fields ordered the way the designer arranged them.
     */
    public static function orderedFields(array $settings): array
    {
        $fields = $settings['fields'] ?? [];
        if (! is_array($fields)) {
            return [];
        }

        $fields = array_filter($fields, fn ($field) => is_array($field) && ($field['visible'] ?? false));
        uasort($fields, fn ($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));

        return $fields;
    }

    /**
     * Whether quantities print as a weight, from the sale setting "Quantity
     * Label In Print" (`print_quantity_label`: quantity | weight). In weight
     * mode the print cart asks for grams per row and the Qty field prints them.
     */
    public static function usesWeight(): bool
    {
        return Configuration::where('key', 'print_quantity_label')->value('value') === 'weight';
    }

    public static function quantityCaption(): string
    {
        return self::usesWeight() ? 'Weight' : 'Qty';
    }

    /**
     * The per-row values a print cart row carries onto its label, kept only
     * where they make sense: the row's MRP always, its grams only when the
     * quantity label is Weight.
     *
     * @param  array<string, mixed>  $item
     * @return array{price: ?float, weight: ?float}
     */
    public static function rowValues(array $settings, array $item): array
    {
        $price = $item['price'] ?? null;
        $weight = $item['weight'] ?? null;

        return [
            'price' => is_numeric($price) ? (float) $price : null,
            'weight' => is_numeric($weight) && (float) $weight > 0 && self::usesWeight() ? (float) $weight : null,
        ];
    }

    /**
     * The price a label prints: the row's own price when it has one, else the MRP.
     *
     * @param  array{price?: ?float, weight?: ?float}  $row
     */
    public static function price(Product $product, float $conversionFactor = 1, array $row = []): float
    {
        return $row['price'] ?? (float) $product->mrp * $conversionFactor;
    }

    public static function weightText(?float $weight): string
    {
        return $weight === null ? '' : number_format($weight, 3).' g';
    }

    /**
     * Rendered value for one text wing field, or '' when there is nothing to show.
     *
     * @param  array{price?: ?float, weight?: ?float}  $row
     */
    public static function fieldValue(
        string $key,
        array $field,
        ?Product $product,
        float $conversionFactor = 1,
        ?Inventory $inventory = null,
        array $row = []
    ): string {
        if (! $product) {
            return '';
        }

        $weight = $row['weight'] ?? null;

        $value = match ($key) {
            'product_name' => (string) $product->name,
            'product_name_arabic' => (string) ($product->name_arabic ?? ''),
            'size' => (string) ($product->size ?? ''),
            'price' => number_format(self::price($product, $conversionFactor, $row), 2),
            'qty' => $weight === null ? self::qtyValue($field, $product, $conversionFactor, $inventory) : self::weightText($weight),
            default => '',
        };

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $limit = (int) ($field['char_limit'] ?? 0);
        if ($limit > 0) {
            $value = mb_substr($value, 0, $limit);
        }

        $prefix = trim((string) ($key === 'qty' && $weight !== null
            ? ($field['weight_prefix'] ?? $field['prefix'] ?? '')
            : ($field['prefix'] ?? '')));

        return $prefix === '' ? $value : $prefix.' '.$value;
    }

    protected static function qtyValue(array $field, Product $product, float $conversionFactor, ?Inventory $inventory): string
    {
        return match ($field['source'] ?? 'unit') {
            'custom' => trim((string) ($field['custom_text'] ?? '')),
            'stock' => $inventory
                ? rtrim(rtrim(number_format((float) $inventory->quantity, 2, '.', ''), '0'), '.')
                : self::unitQty($product, $conversionFactor),
            default => self::unitQty($product, $conversionFactor),
        };
    }

    protected static function unitQty(Product $product, float $conversionFactor): string
    {
        $qty = rtrim(rtrim(number_format($conversionFactor, 2, '.', ''), '0'), '.');
        $unit = $product->relationLoaded('unit') ? $product->unit : $product->unit()->first();
        $unitName = trim((string) ($unit?->code ?? $unit?->name ?? ''));

        return $unitName === '' ? $qty : $qty.' '.$unitName;
    }
}
