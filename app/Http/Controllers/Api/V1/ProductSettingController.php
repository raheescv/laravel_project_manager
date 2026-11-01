<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Settings\UpdateProductConfigurationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ProductConfigurationRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

/**
 * Settings → Product Configuration from the app — the same shared keys the
 * web settings tab writes, behind the same `configuration.settings` gate.
 */
#[Group('Mobile - Settings')]
class ProductSettingController extends Controller
{
    use ApiResponseTrait;

    /**
     * Product configuration.
     *
     * Barcode type, barcode prefix, barcode = SKU and hide out-of-stock items.
     */
    public function index(): JsonResponse
    {
        return $this->sendSuccess(UpdateProductConfigurationAction::current(), 'Product configuration retrieved successfully');
    }

    /**
     * Update product configuration.
     *
     * Send only the keys being changed. Hiding out-of-stock items also needs
     * `sale.create`, as on the web.
     */
    public function update(ProductConfigurationRequest $request, UpdateProductConfigurationAction $action): JsonResponse
    {
        return $this->sendSuccess($action->execute($request->validated(), $request->user()), 'Product configuration updated');
    }
}
