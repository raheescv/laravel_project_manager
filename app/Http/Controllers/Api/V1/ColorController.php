<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Color\GetColorsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\GetColorsRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

#[Group('Product Filter')]
class ColorController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of colors with optional product code filtering.
     * 
     * @queryParam code string Filter colors by product code (partial match). Example: PRD
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "color": "Red",
     *       "product_count": 15
     *     }
     *   ],
     *   "message": "Colors retrieved successfully"
     * }
     */
    public function index(GetColorsAction $action, GetColorsRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Colors retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve colors: '.$e->getMessage());
        }
    }
}
