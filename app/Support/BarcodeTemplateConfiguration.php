<?php

namespace App\Support;

use App\Models\Configuration;

class BarcodeTemplateConfiguration
{
    public const DEFAULT_TEMPLATE_KEY = 'default';

    public const DEFAULT_TYPE = 'standard';

    /**
     * Every label type keyed by its slug: ['standard' => ['label' => ..., 'description' => ...]].
     */
    public static function types(): array
    {
        $types = [];
        foreach (config('barcode_default_configuration.types', []) as $key => $type) {
            $types[$key] = [
                'key' => $key,
                'label' => $type['label'] ?? ucwords(str_replace('_', ' ', $key)),
                'description' => $type['description'] ?? '',
            ];
        }

        return $types;
    }

    public static function typeExists(?string $type): bool
    {
        return $type !== null && array_key_exists($type, config('barcode_default_configuration.types', []));
    }

    public static function defaultType(): string
    {
        $type = config('barcode_default_configuration.default_type', self::DEFAULT_TYPE);

        return self::typeExists($type) ? $type : self::DEFAULT_TYPE;
    }

    /**
     * The untouched defaults for a single label type.
     */
    public static function defaultSettings(?string $type = null): array
    {
        $type = self::typeExists($type) ? $type : self::defaultType();

        $settings = config("barcode_default_configuration.types.{$type}.settings", []);
        $settings['type'] = $type;

        return $settings;
    }

    /**
     * Read the type out of a settings array, falling back to the default type.
     */
    public static function resolveType(array $settings): string
    {
        $type = $settings['type'] ?? null;

        return self::typeExists($type) ? $type : self::defaultType();
    }

    public static function getConfiguration(): array
    {
        $raw = Configuration::where('key', 'barcode_configurations')->value('value');
        $decoded = json_decode($raw ?? '', true);

        return self::normalizeConfiguration(is_array($decoded) ? $decoded : []);
    }

    public static function saveConfiguration(array $configuration): void
    {
        Configuration::updateOrCreate(
            ['key' => 'barcode_configurations'],
            ['value' => json_encode(self::normalizeConfiguration($configuration))]
        );
    }

    public static function normalizeConfiguration(array $configuration): array
    {
        if (self::isLegacySettingsPayload($configuration)) {
            $configuration = [
                'default_template' => self::DEFAULT_TEMPLATE_KEY,
                'templates' => [
                    self::DEFAULT_TEMPLATE_KEY => [
                        'name' => 'Default',
                        'settings' => $configuration,
                    ],
                ],
            ];
        }

        $templates = $configuration['templates'] ?? [];
        if (! is_array($templates) || empty($templates)) {
            $templates = [
                self::DEFAULT_TEMPLATE_KEY => [
                    'name' => 'Default',
                    'settings' => self::defaultSettings(),
                ],
            ];
        }

        $normalizedTemplates = [];
        foreach ($templates as $key => $template) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $name = is_array($template) ? ($template['name'] ?? null) : null;
            $settings = is_array($template) ? ($template['settings'] ?? $template) : [];
            $settings = is_array($settings) ? $settings : [];

            // A type recorded on the template itself wins for legacy rows that
            // were saved before the type lived inside the settings block.
            $type = is_array($template) ? ($template['type'] ?? null) : null;
            $type = self::typeExists($type) ? $type : self::resolveType($settings);

            $normalizedSettings = self::normalizeSettings($settings, $type);

            $normalizedTemplates[$key] = [
                'name' => is_string($name) && trim($name) !== '' ? trim($name) : ucwords(str_replace(['-', '_'], ' ', $key)),
                'type' => $normalizedSettings['type'],
                'settings' => $normalizedSettings,
            ];
        }

        if (empty($normalizedTemplates)) {
            $normalizedTemplates[self::DEFAULT_TEMPLATE_KEY] = [
                'name' => 'Default',
                'type' => self::defaultType(),
                'settings' => self::defaultSettings(),
            ];
        }

        $defaultTemplate = $configuration['default_template'] ?? array_key_first($normalizedTemplates);
        if (! isset($normalizedTemplates[$defaultTemplate])) {
            $defaultTemplate = array_key_first($normalizedTemplates);
        }

        return [
            'default_template' => $defaultTemplate,
            'templates' => $normalizedTemplates,
        ];
    }

    public static function resolveSettings(?string $templateKey = null): array
    {
        $configuration = self::getConfiguration();
        $resolvedTemplate = $templateKey && isset($configuration['templates'][$templateKey])
            ? $templateKey
            : $configuration['default_template'];

        return [
            'template_key' => $resolvedTemplate,
            'settings' => $configuration['templates'][$resolvedTemplate]['settings'],
            'configuration' => $configuration,
        ];
    }

    /**
     * Merge saved settings over the defaults of their own type. The stored type
     * always survives normalisation - it is what every renderer dispatches on.
     */
    public static function normalizeSettings(array $settings, ?string $type = null): array
    {
        $type = self::typeExists($type) ? $type : self::resolveType($settings);

        $normalized = array_replace_recursive(self::defaultSettings($type), $settings);
        $normalized['type'] = $type;
        $normalized = self::normalizeFonts($normalized);

        if ($type === 'jewellery_tag') {
            $normalized = self::normalizeJewelleryTagSettings($normalized);
        }

        return $normalized;
    }

    /**
     * Keep every font key in the tree pointing at a family the app can actually
     * render, whatever a template was saved with before the font picker existed.
     */
    protected static function normalizeFonts(array $settings): array
    {
        $settings['font'] = is_array($settings['font'] ?? null) ? $settings['font'] : [];
        $settings['font']['family'] = BarcodeFonts::normalizeKey($settings['font']['family'] ?? null);
        $settings['font']['weight'] = BarcodeFonts::normalizeWeight($settings['font']['weight'] ?? null);

        return self::normalizeFontKeys($settings);
    }

    protected static function normalizeFontKeys(array $block): array
    {
        foreach ($block as $key => $value) {
            if (is_array($value)) {
                $block[$key] = self::normalizeFontKeys($value);

                continue;
            }

            // An empty family is an element saying "use the template font".
            if ($key === 'font_family') {
                $block[$key] = BarcodeFonts::normalizeKey($value, true);
            }

            if ($key === 'font_weight') {
                $block[$key] = BarcodeFonts::normalizeWeight($value);
            }
        }

        return $block;
    }

    /**
     * The strip width is always the two wings plus the neck, so every consumer
     * (paper size, template list, designer preview) can keep reading `width`.
     */
    protected static function normalizeJewelleryTagSettings(array $settings): array
    {
        $wing = max(5, (float) ($settings['wing_width'] ?? 25));
        $neck = max(0, (float) ($settings['neck_width'] ?? 25));

        $settings['wing_width'] = round($wing, 2);
        $settings['neck_width'] = round($neck, 2);
        $settings['width'] = round(($wing * 2) + $neck, 2);
        $settings['height'] = round(max(3, (float) ($settings['height'] ?? 13)), 2);
        $settings['neck_height'] = round(min((float) ($settings['neck_height'] ?? 5), $settings['height']), 2);
        $settings['inner_padding'] = round(max(0, (float) ($settings['inner_padding'] ?? 1.5)), 2);

        if (! in_array($settings['barcode_wing'] ?? null, ['left', 'right'], true)) {
            $settings['barcode_wing'] = 'left';
        }

        if (! in_array($settings['barcode']['orientation'] ?? null, ['horizontal', 'vertical'], true)) {
            $settings['barcode']['orientation'] = 'horizontal';
        }

        return $settings;
    }

    protected static function isLegacySettingsPayload(array $configuration): bool
    {
        return isset($configuration['width'], $configuration['height'], $configuration['elements']);
    }
}
