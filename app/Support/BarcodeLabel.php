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
     * Font sizes (px) that fit a jewellery tag's text lines into its wing. A
     * line wider than the wing shrinks until it fits (name lines wrap to two
     * lines instead), then the whole stack shrinks evenly when it is taller
     * than the wing. The designer's sizes are the most a line ever gets.
     *
     * Widths are estimated from the character count, close enough for the
     * narrow sans and mono faces labels print in.
     *
     * @param  array<string, array{field: array<string, mixed>, value: string}>  $lines
     * @return array<string, float>
     */
    public static function fitFontSizes(array $settings, array $lines): array
    {
        $pxPerMm = 96 / 25.4;
        $padding = (float) ($settings['inner_padding'] ?? 1.5);
        $width = max(1, (float) ($settings['wing_width'] ?? 25) - $padding * 2) * $pxPerMm;
        $height = max(1, (float) ($settings['height'] ?? 13) - $padding * 2) * $pxPerMm;
        $lineHeight = 1.15;
        $minimum = 4.0;

        $sizes = [];
        $rows = [];
        foreach ($lines as $key => $line) {
            $size = max($minimum, (float) ($line['field']['font_size'] ?? 6));
            $glyph = ($line['field']['bold'] ?? false) ? 0.6 : 0.55;
            $textWidth = max(1, mb_strlen($line['value'])) * $glyph;
            $wraps = in_array($key, ['product_name', 'product_name_arabic'], true);

            if ($wraps) {
                $rows[$key] = $textWidth * $size > $width ? 2 : 1;
            } else {
                $size = max($minimum, min($size, $width / $textWidth));
                $rows[$key] = 1;
            }

            $sizes[$key] = $size;
        }

        $stack = array_sum(array_map(fn (string $key): float => $sizes[$key] * $lineHeight * $rows[$key], array_keys($sizes)));
        $scale = $stack > $height ? $height / $stack : 1;

        return array_map(fn (float $size): float => round(max($minimum, $size * $scale), 1), $sizes);
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

    /**
     * Whether the name lines print the product's main category instead of its
     * own name, from the sale setting "Item Label In Print" (`print_item_label`:
     * product | category).
     */
    public static function usesCategoryName(): bool
    {
        return Configuration::where('key', 'print_item_label')->value('value') === 'category';
    }

    /**
     * The name a label prints: the main category's name in category mode, when
     * the product has one, else the product's own name.
     */
    public static function itemName(Product $product): string
    {
        $category = self::usesCategoryName() ? $product->mainCategory : null;

        return (string) ($category?->name ?: $product->name);
    }

    /**
     * Arabic counterpart of itemName(); in category mode it never falls back to
     * the product's Arabic name when the category has none.
     */
    public static function itemNameArabic(Product $product): string
    {
        if (self::usesCategoryName() && $product->mainCategory) {
            return (string) ($product->mainCategory->name_arabic ?? '');
        }

        return (string) ($product->name_arabic ?? '');
    }

    /**
     * Whether the template prints a price at all: a jewellery tag's price
     * field, or a sticker's price or Arabic price element.
     */
    public static function printsPrice(array $settings): bool
    {
        if (self::type($settings) === 'jewellery_tag') {
            return (bool) ($settings['fields']['price']['visible'] ?? false);
        }

        return (bool) (($settings['price']['visible'] ?? true) || ($settings['price_arabic']['visible'] ?? true));
    }

    /**
     * Whether the template prints the Qty line, which carries the weight in weight mode.
     */
    public static function printsQty(array $settings): bool
    {
        if (self::type($settings) === 'jewellery_tag') {
            return (bool) ($settings['fields']['qty']['visible'] ?? false);
        }

        return (bool) ($settings['qty']['visible'] ?? false);
    }

    public static function quantityCaption(): string
    {
        return self::usesWeight() ? 'Weight' : 'Qty';
    }

    /**
     * The per-row values a print cart row carries onto its label, kept only
     * where they make sense: the row's MRP and tax % always, its grams only
     * when the quantity label is Weight.
     *
     * @param  array<string, mixed>  $item
     * @return array{price: ?float, weight: ?float, tax: ?float}
     */
    public static function rowValues(array $settings, array $item): array
    {
        $price = $item['price'] ?? null;
        $weight = $item['weight'] ?? null;
        $tax = $item['tax'] ?? null;

        return [
            'price' => is_numeric($price) ? (float) $price : null,
            'weight' => is_numeric($weight) && (float) $weight > 0 && self::usesWeight() ? (float) $weight : null,
            'tax' => is_numeric($tax) && (float) $tax >= 0 ? (float) $tax : null,
        ];
    }

    /**
     * The tax % inside a label's price: the row's own, else the product's.
     *
     * @param  array{tax?: ?float}  $row
     */
    public static function taxRate(Product $product, array $row = []): float
    {
        return (float) ($row['tax'] ?? $product->tax ?? 0);
    }

    /**
     * The tax part of a label's price (MRP includes tax).
     *
     * @param  array{price?: ?float, tax?: ?float}  $row
     */
    public static function taxAmount(Product $product, float $conversionFactor = 1, array $row = []): float
    {
        $rate = self::taxRate($product, $row);

        return round(self::price($product, $conversionFactor, $row) * $rate / (100 + $rate), 2);
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

    /**
     * The currency a label's price reads in: the default currency code from Settings.
     */
    public static function currency(): string
    {
        return trim((string) (tenant_cache('base_currency_code') ?: tenant_cache('currency_code')));
    }

    /**
     * The currency beside the Arabic price: the symbol when it is Arabic (ر.ق),
     * else the same one the Latin price uses.
     */
    public static function currencyArabic(): string
    {
        $symbol = trim((string) tenant_cache('currency_symbol'));

        return self::isArabic($symbol) ? $symbol : self::currency();
    }

    private static function isArabic(string $text): bool
    {
        return preg_match('/\p{Arabic}/u', $text) === 1;
    }

    public static function weightText(?float $weight): string
    {
        return $weight === null ? '' : number_format($weight, 3).' g';
    }

    /**
     * Rendered value for one text wing field, or '' when there is nothing to show.
     *
     * `amount` is the price before tax, `tax` its rate and amount, and `price`
     * the total with tax in the Settings currency.
     *
     * @param  array{price?: ?float, weight?: ?float, tax?: ?float}  $row
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
            'product_name' => self::itemName($product),
            'product_name_arabic' => self::itemNameArabic($product),
            'size' => (string) ($product->size ?? ''),
            'price' => number_format(self::price($product, $conversionFactor, $row), 2),
            'amount' => number_format(self::price($product, $conversionFactor, $row) - self::taxAmount($product, $conversionFactor, $row), 2),
            'tax' => self::taxRate($product, $row) > 0
                ? rtrim(rtrim(number_format(self::taxRate($product, $row), 2), '0'), '.').'% '.number_format(self::taxAmount($product, $conversionFactor, $row), 2)
                : '',
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

        $prefix = trim((string) match (true) {
            $key === 'price' => ($field['prefix'] ?? '').' '.self::currency(),
            $key === 'qty' && $weight !== null => $field['weight_prefix'] ?? $field['prefix'] ?? '',
            default => $field['prefix'] ?? '',
        });

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
        $unitName = trim((string) ($unit->code ?? $unit->name ?? ''));

        return $unitName === '' ? $qty : $qty.' '.$unitName;
    }
}
