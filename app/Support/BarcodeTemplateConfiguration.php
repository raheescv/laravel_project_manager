<?php

namespace App\Support;

use App\Models\Configuration;

class BarcodeTemplateConfiguration
{
    public const DEFAULT_TEMPLATE_KEY = 'default';

    public static function defaultSettings(): array
    {
        return config('barcode_default_configuration');
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

            $normalizedTemplates[$key] = [
                'name' => is_string($name) && trim($name) !== '' ? trim($name) : ucwords(str_replace(['-', '_'], ' ', $key)),
                'settings' => self::normalizeSettings(is_array($settings) ? $settings : []),
            ];
        }

        if (empty($normalizedTemplates)) {
            $normalizedTemplates[self::DEFAULT_TEMPLATE_KEY] = [
                'name' => 'Default',
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

    public static function normalizeSettings(array $settings): array
    {
        return array_replace_recursive(self::defaultSettings(), $settings);
    }

    protected static function isLegacySettingsPayload(array $configuration): bool
    {
        return isset($configuration['width'], $configuration['height'], $configuration['elements']);
    }
}
