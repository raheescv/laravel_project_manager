<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Single source of truth for resolving the company logo used across printed
 * documents (LPO, vouchers, checklists, …).
 *
 * Resolution order:
 *   1. An optional dedicated header-image config key (e.g. `lpo_header_image`).
 *   2. The general `logo` configuration value (absolute URL or relative path).
 *   3. The cached `logo` URL (may be stale, kept as a fallback).
 *   4. The bundled fallback asset shipped with the app.
 */
class CompanyLogoResolver
{
    /**
     * Resolve the logo to a LOCAL file path on disk, or null when none exists.
     * Preferred for DomPDF, which renders file paths reliably (it drops
     * data-URIs and can't always fetch remote URLs).
     *
     * @param  string|null  $preferKey  Config key whose image takes priority (e.g. 'lpo_header_image').
     */
    public static function path(?string $preferKey = null): ?string
    {
        foreach (self::candidates($preferKey) as $candidate) {
            if ($candidate && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Resolve the logo to a base64 data URI, or null when none exists.
     * Preferred for Browsershot, which embeds data URIs reliably.
     *
     * @param  string|null  $preferKey  Config key whose image takes priority (e.g. 'lpo_header_image').
     */
    public static function dataUri(?string $preferKey = null): ?string
    {
        $path = self::path($preferKey);
        if (! $path) {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'png';
        $mime = $ext === 'svg' ? 'image/svg+xml' : 'image/'.$ext;

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }

    /**
     * Ordered list of candidate local file paths to probe.
     *
     * @return array<int, string>
     */
    private static function candidates(?string $preferKey): array
    {
        $candidates = [];

        // 1. Dedicated header image (a per-document logo upload).
        if ($preferKey) {
            $candidates = array_merge($candidates, self::pathsFor(Configuration::where('key', $preferKey)->value('value')));
        }

        // 2. General company logo from configuration.
        $candidates = array_merge($candidates, self::pathsFor(Configuration::where('key', 'logo')->value('value')));

        // 3. Cached logo URL (may be stale, but a useful fallback).
        $candidates = array_merge($candidates, self::pathsFor(cache('logo')));

        // 4. Bundled fallback shipped with the app.
        $candidates[] = public_path('assets/img/logo.svg');

        return $candidates;
    }

    /**
     * Expand a stored logo value (absolute URL or relative path) into the
     * likely disk locations it could map to.
     *
     * @return array<int, string>
     */
    private static function pathsFor(?string $value): array
    {
        if (! $value) {
            return [];
        }

        // Reduce an absolute URL (…/storage/company_image/x.png) to its path component.
        $relative = Str::startsWith($value, ['http://', 'https://'])
            ? (parse_url($value, PHP_URL_PATH) ?: $value)
            : $value;
        $relative = ltrim($relative, '/');
        // Drop a leading `storage/` segment so it maps onto the public disk root.
        $relative = Str::after($relative, 'storage/');

        return [
            Storage::disk('public')->path($relative),
            storage_path('app/public/'.$relative),
            public_path('storage/'.$relative),
            public_path($relative),
        ];
    }
}
