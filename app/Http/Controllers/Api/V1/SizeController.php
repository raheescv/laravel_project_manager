<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Size\GetSizesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\GetSizesRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

#[Group('Product Filter')]
class SizeController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of sizes with optional product code filtering.
     * 
     * @queryParam code string Filter sizes by product code (partial match). Example: PRD
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "size": "Large",
     *       "product_count": 45
     *     }
     *   ],
     *   "message": "Sizes retrieved successfully"
     * }
     */
    public function index(GetSizesAction $action, GetSizesRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Sizes retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve sizes: '.$e->getMessage());
        }
    }
}
