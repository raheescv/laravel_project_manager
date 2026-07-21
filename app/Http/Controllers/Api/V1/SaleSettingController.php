<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
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
     * POS does.
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
            ], 'Sale settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sale settings: '.$e->getMessage());
        }
    }
}
