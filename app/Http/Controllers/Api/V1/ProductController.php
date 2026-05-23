<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Product\GetAction;
use App\Actions\V1\Product\GetProductAction;
use App\Actions\V1\Product\GetProductsAction;
use App\Actions\V1\Product\ListAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\GetProductRequest;
use App\Http\Requests\V1\GetProductsRequest;
use App\Http\Requests\V1\Product\SearchRequest;
use App\Http\Resources\V1\Product\ProductResource;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

#[Group('Product')]
class ProductController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of products with filtering and pagination.
     */
    public function index(GetProductsAction $action, GetProductsRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Products retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve products: '.$e->getMessage());
        }
    }

    /**
     * Display the specified product.
     */
    public function show(GetProductAction $action, GetProductRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Product retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve product: '.$e->getMessage());
        }
    }

    /**
     * Display the specified product.
     */
    public function get(GetProductAction $action, GetProductRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Product retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve product: '.$e->getMessage());
        }
    }

    /**
     * List products (mobile app).
     *
     * Retrieves sellable products sorted alphabetically, with optional keyword search.
     */
    #[Group('Mobile - Products')]
    public function mobileIndex(ListAction $action, SearchRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve products: '.$e->getMessage());
        }
    }

    /**
     * Fetch product details (mobile app).
     *
     * Retrieves detailed information for a specific product.
     */
    #[Group('Mobile - Products')]
    public function mobileShow(GetAction $action, int $product): JsonResponse
    {
        try {
            $result = $action->execute($product);

            return $this->sendSuccess(new ProductResource($result), 'Product retrieved successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Product not found.');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve product: '.$e->getMessage());
        }
    }
}
