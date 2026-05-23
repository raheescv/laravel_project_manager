<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Sale\CreateAction;
use App\Actions\V1\Sale\GetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Sale\StoreRequest;
use App\Http\Resources\V1\Sale\SaleResource;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

#[Group('Mobile - Sales')]
class SaleController extends Controller
{
    use ApiResponseTrait;

    /**
     * View sale.
     *
     * Retrieves a sale with line items and payments, formatted for printing.
     */
    public function show(GetAction $action, int $sale): JsonResponse
    {
        try {
            $result = $action->execute($sale);

            return $this->sendSuccess(new SaleResource($result), 'Sale retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Sale not found.');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sale: '.$e->getMessage());
        }
    }

    /**
     * Create sale.
     *
     * Creates a completed sale from the final sale data sent by the app and records the payment.
     */
    public function store(CreateAction $action, StoreRequest $request): JsonResponse
    {
        try {
            $sale = $action->execute($request);

            return $this->sendSuccess(new SaleResource($sale), 'Sale saved successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to save sale: '.$e->getMessage(), [], 422);
        }
    }
}
