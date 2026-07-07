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
     * Returns the default quantity and tip availability configured under
     * Settings → Sale Configuration, so the mobile app can prefill new cart
     * lines and show or hide the "Add a Tip" option the same way the web
     * POS does.
     */
    public function index(): JsonResponse
    {
        try {
            $defaultQuantity = (float) (Configuration::where('key', 'default_quantity')->value('value') ?? '0.001');
            $tipEnabled = (Configuration::where('key', 'enable_tip')->value('value') ?? 'yes') === 'yes';

            return $this->sendSuccess([
                'default_quantity' => $defaultQuantity,
                'tip_enabled' => $tipEnabled,
            ], 'Sale settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sale settings: '.$e->getMessage());
        }
    }
}
