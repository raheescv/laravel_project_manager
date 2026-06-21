<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\SaleReturn\CreateAction;
use App\Actions\V1\SaleReturn\GetAction;
use App\Actions\V1\SaleReturn\ListAction;
use App\Actions\V1\SaleReturn\ReturnableSaleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SaleReturn\IndexRequest;
use App\Http\Requests\V1\SaleReturn\StoreRequest;
use App\Http\Resources\V1\SaleReturn\ReturnableSaleResource;
use App\Http\Resources\V1\SaleReturn\SaleReturnResource;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

#[Group('Mobile - Sale Returns')]
class SaleReturnController extends Controller
{
    use ApiResponseTrait;

    /**
     * List sale returns.
     *
     * Returns a paginated list of sale returns for the authenticated user's assigned
     * branches, with optional filters (search, status, date range, customer, payment method).
     */
    public function index(ListAction $action, IndexRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Sale returns retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sale returns: '.$e->getMessage());
        }
    }

    /**
     * Returnable sale.
     *
     * Retrieves a sale with its lines and the remaining returnable quantity per line,
     * used to seed the "New Return" screen.
     */
    public function fromSale(ReturnableSaleAction $action, int $sale): JsonResponse
    {
        try {
            $result = $action->execute($sale);

            return $this->sendSuccess(new ReturnableSaleResource($result), 'Sale retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Sale not found.');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sale: '.$e->getMessage());
        }
    }

    /**
     * View sale return.
     *
     * Retrieves a sale return with line items and refund payments, formatted for printing.
     */
    public function show(GetAction $action, int $saleReturn): JsonResponse
    {
        try {
            $result = $action->execute($saleReturn);

            return $this->sendSuccess(new SaleReturnResource($result), 'Sale return retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Sale return not found.');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sale return: '.$e->getMessage());
        }
    }

    /**
     * Create sale return.
     *
     * Creates a sale return against an existing sale from the final data sent by the app
     * and records the refund payment.
     */
    public function store(CreateAction $action, StoreRequest $request): JsonResponse
    {
        try {
            $saleReturn = $action->execute($request);

            return $this->sendSuccess(new SaleReturnResource($saleReturn), 'Sale return saved successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Failed to save sale return: '.$e->getMessage(), [], 422);
        }
    }
}
