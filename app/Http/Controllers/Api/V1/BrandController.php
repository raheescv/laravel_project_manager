<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Brand\GetBrandsAction;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BrandController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of brands.
     *
     * @group Product Filter
     * @subgroup Brands
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
    public function index(GetBrandsAction $action): JsonResponse
    {
        try {
            $result = $action->execute();

            return $this->sendSuccess($result, 'Brands retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve brands: '.$e->getMessage());
        }
    }
}
