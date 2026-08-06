<?php

namespace App\Support;

/**
 * The typefaces a barcode template can print in.
 *
 * The catalogue lives in config/barcode_default_configuration.php. Templates
 * store a family *key* ('plex_sans'), never a raw CSS font stack, so a saved
 * template can never ask for a font that is not installed anywhere.
 *
 * Resolution is two level: the template names one family, and an element may
 * override it. An empty `font_family` on an element means "inherit".
 */
class BarcodeFonts
{
    public const INHERIT = '';

    /** Base64 payloads keyed by file path, so one render encodes each face once. */
    protected static array $encoded = [];

    public static function families(): array
    {
        $families = config('barcode_default_configuration.fonts.families', []);

        return is_array($families) ? $families : [];
    }

    /**
     * The catalogue the designer renders its font pickers from.
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::families() as $key => $family) {
            $options[$key] = [
                'key' => $key,
                'label' => $family['label'] ?? ucwords(str_replace('_', ' ', $key)),
                'description' => $family['description'] ?? '',
                'stack' => $family['stack'] ?? 'sans-serif',
            ];
        }

        return $options;
    }

    /**
     * @return array<int, string> weight => label
     */
    public static function weights(): array
    {
        $weights = config('barcode_default_configuration.fonts.weights', []);

        return is_array($weights) && $weights !== [] ? $weights : [400 => 'Regular', 700 => 'Bold'];
    }

    public static function defaultKey(): string
    {
        $key = config('barcode_default_configuration.fonts.default');

        if (self::exists($key)) {
            return $key;
        }

        return (string) array_key_first(self::families()) ?: 'grotesque';
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::families());
    }

    /**
     * Coerce anything stored in a template to a family key we can actually
     * render. An element that names a family we no longer ship falls back to
     * inheriting the template font rather than pinning itself to the default.
     */
    public static function normalizeKey(mixed $key, bool $allowInherit = false): string
    {
        $key = is_string($key) ? trim($key) : '';

        if (self::exists($key)) {
            return $key;
        }

        return $allowInherit ? self::INHERIT : self::defaultKey();
    }

    public static function normalizeWeight(mixed $weight, int $fallback = 400): int
    {
        $weight = (int) $weight;

        return array_key_exists($weight, self::weights()) ? $weight : $fallback;
    }

    /**
     * The CSS font-family value for a family key.
     */
    public static function stack(?string $key): string
    {
        $families = self::families();
        $key = self::normalizeKey($key);

        return $families[$key]['stack'] ?? 'sans-serif';
    }

    public static function templateKey(array $settings): string
    {
        return self::normalizeKey($settings['font']['family'] ?? null);
    }

    public static function templateStack(array $settings): string
    {
        return self::stack(self::templateKey($settings));
    }

    public static function templateWeight(array $settings, int $fallback = 400): int
    {
        return self::normalizeWeight($settings['font']['weight'] ?? null, $fallback);
    }

    /**
     * Font stack for one settings block (a standard element or a tag field),
     * falling back to the template font when the block does not override it.
     */
    public static function blockStack(array $settings, mixed $block): string
    {
        $key = is_array($block) ? self::normalizeKey($block['font_family'] ?? null, true) : self::INHERIT;

        return self::stack($key === self::INHERIT ? self::templateKey($settings) : $key);
    }

    public static function blockWeight(array $settings, mixed $block, ?int $fallback = null): int
    {
        $fallback ??= self::templateWeight($settings);

        return is_array($block) ? self::normalizeWeight($block['font_weight'] ?? null, $fallback) : $fallback;
    }

    public static function elementStack(array $settings, string $element): string
    {
        return self::blockStack($settings, $settings[$element] ?? null);
    }

    public static function elementWeight(array $settings, string $element, ?int $fallback = null): int
    {
        return self::blockWeight($settings, $settings[$element] ?? null, $fallback);
    }

    /**
     * `@font-face` rules for every shipped family a template actually uses.
     *
     * Browsershot renders the label HTML with every domain blocked and no base
     * URL, so a PDF can only see a font that travels inside the document -
     * hence `$embed`. The on screen preview links the same files by URL instead
     * so the browser can cache them between preview refreshes.
     */
    public static function faceCss(?array $settings = null, bool $embed = true): string
    {
        $css = '';
        $wanted = self::usedWeights($settings);

        foreach (self::embeddableFamilies($settings) as $family) {
            $name = $family['embed']['family'] ?? null;
            $files = $family['embed']['files'] ?? [];

            if (! is_string($name) || ! is_array($files)) {
                continue;
            }

            foreach ($files as $weight => $path) {
                if ($wanted !== null && ! in_array((int) $weight, $wanted, true)) {
                    continue;
                }

                $src = $embed ? self::dataUri($path) : self::assetUrl($path);
                if ($src === null) {
                    continue;
                }

                $css .= "@font-face{font-family:'{$name}';font-style:normal;font-weight:".(int) $weight.";font-display:block;src:url({$src}) format('truetype');}";
            }
        }

        return $css;
    }

    public static function styleTag(?array $settings = null, bool $embed = true): string
    {
        $css = self::faceCss($settings, $embed);

        return $css === '' ? '' : "<style>{$css}</style>";
    }

    /**
     * Shipped families referenced by these settings - or all of them when no
     * settings are given (the designer previews every option).
     */
    protected static function embeddableFamilies(?array $settings): array
    {
        $families = array_filter(self::families(), fn ($family) => ! empty($family['embed']['files']));

        if ($settings === null) {
            return $families;
        }

        $used = self::usedKeys($settings);

        return array_intersect_key($families, array_flip($used));
    }

    /**
     * Which shipped faces this template needs, so a label that never prints
     * bold does not carry a bold face it will not use. Null means "all of
     * them" - the designer previews every option.
     *
     * @return array<int, int>|null
     */
    protected static function usedWeights(?array $settings): ?array
    {
        if ($settings === null) {
            return null;
        }

        $weights = [self::templateWeight($settings)];

        array_walk_recursive($settings, function ($value, $key) use (&$weights) {
            if ($key === 'font_weight') {
                $weights[] = (int) $value;
            }

            // A tag field says bold with a flag rather than a weight.
            if ($key === 'bold' && $value) {
                $weights[] = 700;
            }
        });

        // Faces are shipped at 400 and 700: anything semi bold or heavier is
        // drawn with the bold face, everything else with the regular one.
        $needed = [400];
        foreach ($weights as $weight) {
            if ($weight >= 600) {
                $needed[] = 700;
                break;
            }
        }

        return $needed;
    }

    /**
     * Every family key this template can reach: the template font plus any
     * element or field that names its own.
     */
    protected static function usedKeys(array $settings): array
    {
        $keys = [self::templateKey($settings)];

        array_walk_recursive($settings, function ($value, $key) use (&$keys) {
            if ($key === 'font_family' && is_string($value) && self::exists($value)) {
                $keys[] = $value;
            }
        });

        return array_values(array_unique($keys));
    }

    protected static function assetUrl(string $path): ?string
    {
        return is_file(public_path($path)) ? asset($path) : null;
    }

    protected static function dataUri(string $path): ?string
    {
        if (array_key_exists($path, self::$encoded)) {
            return self::$encoded[$path];
        }

        $file = public_path($path);
        $uri = is_file($file) ? 'data:font/ttf;base64,'.base64_encode((string) file_get_contents($file)) : null;

        return self::$encoded[$path] = $uri;
    }
}
