<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Brand\GetBrandsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\GetBrandsRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

#[Group('Product Filter')]
class BrandController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of brands.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Apple",
     *       "product_count": 25
     *     }
     *   ],
     *   "message": "Brands retrieved successfully"
     * }
     */
    public function index(GetBrandsAction $action, GetBrandsRequest $request): JsonResponse
    {
        try {
            $result = $action->execute(collect($request->all()));

            return $this->sendSuccess($result, 'Brands retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve brands: '.$e->getMessage());
        }
    }
}
