<?php

namespace App\Support;

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
     * Rendered value for one text wing field, or '' when there is nothing to show.
     */
    public static function fieldValue(
        string $key,
        array $field,
        ?Product $product,
        float $conversionFactor = 1,
        ?Inventory $inventory = null
    ): string {
        if (! $product) {
            return '';
        }

        $value = match ($key) {
            'product_name' => (string) $product->name,
            'product_name_arabic' => (string) ($product->name_arabic ?? ''),
            'size' => (string) ($product->size ?? ''),
            'price' => number_format((float) $product->mrp * $conversionFactor, 2),
            'qty' => self::qtyValue($field, $product, $conversionFactor, $inventory),
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

        $prefix = trim((string) ($field['prefix'] ?? ''));

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
