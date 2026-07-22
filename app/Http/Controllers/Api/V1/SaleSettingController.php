<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Services\CompanyLogoResolver;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Mobile - Settings')]
class SaleSettingController extends Controller
{
    use ApiResponseTrait;

    /**
     * Sale configuration used by the POS.
     *
     * Returns the default quantity, tip availability and the default
     * Product/Service filter configured under Settings → Sale Configuration,
     * so the mobile app can prefill new cart lines, show or hide the "Add a
     * Tip" option and preselect the product-type filter the same way the web
     * POS does. Also carries the thermal-print options so the in-app receipt
     * (buildReceiptPdf) follows the same configuration as the web invoice
     * print (SaleHelper::saleInvoice / sale/print.blade.php).
     */
    public function index(): JsonResponse
    {
        try {
            $defaultQuantity = (float) (Configuration::where('key', 'default_quantity')->value('value') ?? '0.001');
            $tipEnabled = (Configuration::where('key', 'enable_tip')->value('value') ?? 'yes') === 'yes';
            // '' = All Types; 'product' / 'service' narrow the POS catalog. Falls
            // back to 'service' when the key is missing (matches the web POS).
            $defaultProductType = Configuration::where('key', 'default_product_type')->value('value') ?? 'service';

            return $this->sendSuccess([
                'default_quantity' => $defaultQuantity,
                'tip_enabled' => $tipEnabled,
                'default_product_type' => $defaultProductType,
                // Defaults and yes/no semantics mirror SaleHelper::saleInvoice
                // and the checks in sale/print.blade.php exactly, so the app
                // receipt matches what the web would print.
                'print' => [
                    'style' => Configuration::where('key', 'thermal_printer_style')->value('value') ?? 'with_arabic',
                    'footer_english' => Configuration::where('key', 'thermal_printer_footer_english')->value('value'),
                    'footer_arabic' => Configuration::where('key', 'thermal_printer_footer_arabic')->value('value'),
                    'show_discount' => Configuration::where('key', 'enable_discount_in_print')->value('value') === 'yes',
                    'show_total_quantity' => Configuration::where('key', 'enable_total_quantity_in_print')->value('value') === 'yes',
                    'show_barcode' => (Configuration::where('key', 'enable_barcode_in_print')->value('value') ?? 'yes') === 'yes',
                    'show_logo' => Configuration::where('key', 'enable_logo_in_print')->value('value') === 'yes',
                    // Opaque cache-buster: changes when a new logo is uploaded so
                    // the app knows to re-download GET /settings/logo.
                    'logo_version' => Configuration::where('key', 'logo')->value('value') ?? 'default',
                    'quantity_label' => Configuration::where('key', 'print_quantity_label')->value('value') ?? 'quantity',
                ],
            ], 'Sale settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sale settings: '.$e->getMessage());
        }
    }

    /**
     * The company logo image (for the receipt header).
     *
     * Streams the logo configured under Settings → Company Profile — the same
     * image the web invoice prints — falling back to the bundled default. The
     * app caches the bytes locally (keyed by `print.logo_version` from
     * GET /settings/sale) so receipts print offline.
     */
    public function logo(): mixed
    {
        $path = CompanyLogoResolver::path();
        if (! $path) {
            return $this->sendNotFoundError('No logo configured');
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'png';
        $mime = $ext === 'svg' ? 'image/svg+xml' : 'image/'.($ext === 'jpg' ? 'jpeg' : $ext);

        return response()->file($path, ['Content-Type' => $mime]);
    }
}
