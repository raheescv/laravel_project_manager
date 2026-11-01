<?php

namespace App\Actions\Settings;

use App\Models\Configuration;
use App\Models\User;
use App\Support\TenantCache;

/**
 * Settings → Product Configuration, shared by the web settings tab and the
 * mobile app so both write the same keys with the same rules.
 */
class UpdateProductConfigurationAction
{
    /**
     * The keys this screen owns, with the value each reads as when unset.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'barcode_type' => '',
        'barcode_prefix' => '',
        'sync_barcode_to_code' => 'no',
        'hide_out_of_stock_sale_items' => 'no',
    ];

    /**
     * @return array<string, string>
     */
    public static function current(): array
    {
        $stored = Configuration::whereIn('key', array_keys(self::DEFAULTS))->pluck('value', 'key');

        return collect(self::DEFAULTS)->map(fn (string $default, string $key) => $stored[$key] ?? $default)->all();
    }

    /**
     * Write the keys present in [data]. Hiding out-of-stock items changes what
     * the POS sells, so it also needs `sale.create` — as on the web form.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public function execute(array $data, User $user): array
    {
        foreach (self::DEFAULTS as $key => $default) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            if ($key === 'hide_out_of_stock_sale_items' && ! $user->can('sale.create')) {
                continue;
            }
            Configuration::updateOrCreate(['key' => $key], ['value' => $data[$key] ?? $default]);
        }
        TenantCache::forget('barcode_prefix');

        return self::current();
    }
}
