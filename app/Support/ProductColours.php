<?php

namespace App\Support;

use App\Models\Configuration;

/**
 * The colours a tenant has used on product variants (name + hex), kept in the
 * `product_colours` configuration so the Variants tab offers them again next
 * time. Names are matched case-insensitively: "black" and "Black" are one colour.
 */
final class ProductColours
{
    public const KEY = 'product_colours';

    /** Suggested shades for the colour chooser (name, hex), offered to every tenant. */
    public const SUGGESTED = [
        ['Black', '#1f2328'], ['Charcoal', '#36393f'], ['Grey', '#9ca3af'], ['Silver', '#c0c4cc'], ['White', '#f8fafc'], ['Cream', '#f5ecd7'], ['Beige', '#e7dcc4'], ['Khaki', '#b9a77a'],
        ['Tan', '#b08155'], ['Brown', '#78350f'], ['Maroon', '#7f1d1d'], ['Burgundy', '#6d1a2c'], ['Red', '#dc2626'], ['Coral', '#f87171'], ['Orange', '#ea580c'], ['Mustard', '#ca8a04'],
        ['Yellow', '#eab308'], ['Gold', '#c9a227'], ['Olive', '#65712b'], ['Lime', '#84cc16'], ['Green', '#15803d'], ['Forest Green', '#14532d'], ['Mint', '#6ee7b7'], ['Teal', '#0f766e'],
        ['Sky Blue', '#38bdf8'], ['Blue', '#2563eb'], ['Royal Blue', '#1d4ed8'], ['Navy', '#1e3a8a'], ['Lavender', '#a78bfa'], ['Purple', '#7e22ce'], ['Pink', '#ec4899'], ['Rose', '#e11d48'],
    ];

    /** @return array<string, string> colour name => hex: the tenant's own colours first, then the suggestions */
    public static function palette(): array
    {
        $palette = [];
        foreach (self::all() as $colour) {
            $palette[$colour['name']] = $colour['hex'];
        }
        foreach (self::SUGGESTED as [$name, $hex]) {
            $palette[$name] ??= $hex;
        }

        return $palette;
    }

    /** The product form's colour picker stores a hex code ("#ff0000") rather than a name. */
    public static function isHex(?string $value): bool
    {
        return (bool) preg_match('/^#[0-9a-f]{6}$/i', trim((string) $value));
    }

    /**
     * What to call a stored colour on screen: a name stays as it is, a hex code
     * becomes the tenant's name for that exact hex, else the nearest suggested
     * shade ("#ff0000" → "Red").
     */
    public static function displayName(?string $value): string
    {
        $value = trim((string) $value);
        if (! self::isHex($value)) {
            return $value;
        }
        $hex = strtolower($value);
        foreach (self::all() as $colour) {
            if ($colour['hex'] === $hex && ! self::isHex($colour['name'])) {
                return $colour['name'];
            }
        }

        return self::nearestName($hex);
    }

    /** The suggested shade closest to [$hex], weighted the way the eye sees green > blue > red. */
    public static function nearestName(string $hex): string
    {
        [$r, $g, $b] = self::rgb($hex);
        $best = self::SUGGESTED[0][0];
        $bestDistance = PHP_INT_MAX;
        foreach (self::SUGGESTED as [$name, $shade]) {
            [$r2, $g2, $b2] = self::rgb($shade);
            $distance = ($r - $r2) ** 2 * 2 + ($g - $g2) ** 2 * 4 + ($b - $b2) ** 2 * 3;
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $name;
            }
        }

        return $best;
    }

    /** @return array{0: int, 1: int, 2: int} */
    private static function rgb(string $hex): array
    {
        $n = hexdec(ltrim($hex, '#'));

        return [($n >> 16) & 255, ($n >> 8) & 255, $n & 255];
    }

    /** Hex for a stored colour (a hex code is its own hex; names case-insensitive), grey when unknown. */
    public static function hexFor(?string $name, ?array $palette = null): string
    {
        if (self::isHex($name)) {
            return strtolower(trim((string) $name));
        }
        $palette ??= self::palette();
        $needle = mb_strtolower(trim((string) $name));
        foreach ($palette as $known => $hex) {
            if (mb_strtolower($known) === $needle) {
                return $hex;
            }
        }

        return '#9ca3af';
    }

    /** @return array<int, array{name: string, hex: string}> */
    public static function all(): array
    {
        $raw = Configuration::query()->where('key', self::KEY)->value('value');
        $decoded = json_decode((string) $raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($colour) => self::clean($colour['name'] ?? null, $colour['hex'] ?? null), $decoded)));
    }

    /**
     * Add colours the tenant does not have yet. Existing names keep their hex.
     *
     * @param  array<int, array{name: string, hex: string}>  $colours
     */
    public static function remember(array $colours): void
    {
        $known = self::all();
        $names = array_map(fn (array $colour) => mb_strtolower($colour['name']), $known);
        $added = false;

        foreach ($colours as $colour) {
            $clean = self::clean($colour['name'] ?? null, $colour['hex'] ?? null);
            if (! $clean || in_array(mb_strtolower($clean['name']), $names, true)) {
                continue;
            }
            $known[] = $clean;
            $names[] = mb_strtolower($clean['name']);
            $added = true;
        }

        if ($added) {
            Configuration::updateOrCreate(['key' => self::KEY], ['value' => json_encode($known)]);
        }
    }

    /** @return array{name: string, hex: string}|null */
    private static function clean(?string $name, ?string $hex): ?array
    {
        $name = trim(preg_replace('/[<>"&|]/', '', (string) $name));
        $hex = strtolower(trim((string) $hex));

        if ($name === '' || self::isHex($name) || ! preg_match('/^#[0-9a-f]{6}$/', $hex)) {
            return null;
        }

        return ['name' => $name, 'hex' => $hex];
    }
}
