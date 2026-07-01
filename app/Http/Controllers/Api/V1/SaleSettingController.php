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
     * Returns the default quantity configured under Settings → Sale
     * Configuration, so the mobile app can prefill new cart lines and the
     * quantity stepper the same way the web POS does.
     */
    public function index(): JsonResponse
    {
        try {
            $defaultQuantity = (float) (Configuration::where('key', 'default_quantity')->value('value') ?? '0.001');

            return $this->sendSuccess([
                'default_quantity' => $defaultQuantity,
            ], 'Sale settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sale settings: '.$e->getMessage());
        }
    }
}
