<?php

namespace App\Actions\V1\Catalog;

use App\Actions\Product\CreateAction;
use App\Actions\Product\UpdateAction;
use App\Actions\Settings\UpdateProductConfigurationAction;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Create or update a product / service from the mobile form by delegating to
 * the web CreateAction / UpdateAction, so validation, barcodes, inventory rows
 * and quick-created departments behave exactly as they do on the web.
 *
 * A double-tapped create is already guarded: names are unique per type, so the
 * second request fails validation instead of making a copy.
 */
class SaveAction
{
    /**
     * @param  array<string, mixed>  $data  SaveRequest::validated()
     */
    public function execute(string $type, array $data, User $user, ?Product $existing = null): Product
    {
        $payload = ['type' => $type, ...$data];
        $payload['priority'] = (int) ($payload['priority'] ?? 0);

        // The web form starts every item on the default base unit; a service
        // never shows the field, but the column cannot be empty.
        $payload['unit_id'] = ($payload['unit_id'] ?? null) ?: $existing?->unit_id ?: Unit::defaultBaseUnit()?->id;

        if ($existing) {
            // Fields the form leaves blank keep what the item has — the web form
            // always posts them back, and Product::rules requires both.
            foreach (['code', 'barcode_number'] as $key) {
                if (($payload[$key] ?? '') === '') {
                    $payload[$key] = $existing->{$key};
                }
            }
        } elseif ($type === 'product') {
            $payload = $this->syncBarcodeAndCode($payload);
        }

        return DB::transaction(function () use ($payload, $user, $existing): Product {
            $response = $existing
                ? (new UpdateAction())->execute($payload, $existing->id, $user->id)
                : (new CreateAction())->execute($payload, $user->id);

            if (! $response['success']) {
                throw new RuntimeException($response['message']);
            }

            return $response['data']->fresh(['department', 'mainCategory', 'subCategory', 'unit', 'brand', 'images']);
        });
    }

    /**
     * With "Barcode and UPC/EAN/ISBN/SKU Equal" on, one value fills both —
     * the same pre-step the web Product page runs before it creates.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function syncBarcodeAndCode(array $payload): array
    {
        if (UpdateProductConfigurationAction::current()['sync_barcode_to_code'] !== 'yes') {
            return $payload;
        }
        $barcode = trim((string) ($payload['barcode_number'] ?? ''));
        $code = trim((string) ($payload['code'] ?? ''));
        if ($barcode !== '') {
            $payload['code'] = $barcode;
        } elseif ($code !== '') {
            $payload['barcode_number'] = $code;
        }

        return $payload;
    }
}
