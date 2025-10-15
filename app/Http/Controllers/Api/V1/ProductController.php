<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Product\GetProductAction;
use App\Actions\V1\Product\GetProductsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\GetProductRequest;
use App\Models\Product;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

#[Group('Product')]
class ProductController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of products with filtering and pagination.
     */
    public function index(GetProductsAction $action, GetProductRequest $request): JsonResponse
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
    public function show(GetProductAction $action, Product $product): JsonResponse
    {
        try {
            $result = $action->execute($product);

            return $this->sendSuccess($result, 'Product retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve product: '.$e->getMessage());
        }
    }
}
