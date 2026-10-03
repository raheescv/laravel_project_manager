<?php

namespace App\Support;

use Illuminate\Support\Arr;

/**
 * Which look the sign-in screen wears for the current tenant.
 *
 * Settings → Login Page stores a layout and a live background per tenant;
 * either can be "random", which is resolved afresh on every page load.
 */
class LoginScreen
{
    public const RANDOM = 'random';

    /** @var array<string, string> */
    public const LAYOUTS = [
        'split' => 'Split Horizon',
        'frosted' => 'Frosted Canvas',
    ];

    /** @var array<string, string> */
    public const BACKGROUNDS = [
        'globe' => 'Live Globe',
        'network' => 'Network',
        'grid' => 'Horizon Grid',
    ];

    public static function layoutSetting(): string
    {
        return static::sanitize(tenant_cache('login_layout'), self::LAYOUTS);
    }

    public static function backgroundSetting(): string
    {
        return static::sanitize(tenant_cache('login_background'), self::BACKGROUNDS);
    }

    /**
     * The concrete layout and background for this page load, randoms resolved.
     *
     * @return array{layout: string, background: string}
     */
    public static function resolve(): array
    {
        return [
            'layout' => static::pick(static::layoutSetting(), self::LAYOUTS),
            'background' => static::pick(static::backgroundSetting(), self::BACKGROUNDS),
        ];
    }

    /**
     * The sign-in copy for the tenant's system type (config/modules.php →
     * login), any key the system leaves out filled from 'default'.
     *
     * @return array{headline: string, highlight: string, lede: string, tagline: string, features: array<int, array{icon: string, title: string, caption: string}>}
     */
    public static function copy(): array
    {
        $copy = config('modules.login', []);
        $system = ModuleAccess::activeSystem();

        return array_replace($copy['default'] ?? [], $system ? ($copy[$system] ?? []) : []);
    }

    /**
     * @param  array<string, string>  $options
     */
    public static function sanitize(mixed $value, array $options): string
    {
        return is_string($value) && array_key_exists($value, $options) ? $value : self::RANDOM;
    }

    /**
     * @param  array<string, string>  $options
     */
    protected static function pick(string $value, array $options): string
    {
        return $value === self::RANDOM ? Arr::random(array_keys($options)) : $value;
    }
}
