<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Category\GetMainCategoriesAction;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

#[Group('Product Filter')]
class CategoryController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of main categories.
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Electronics",
     *       "product_count": 150
     *     }
     *   ],
     *   "message": "Main categories retrieved successfully"
     * }
     */
    public function index(Request $request, GetMainCategoriesAction $action): JsonResponse
    {
        try {
            $result = $action->execute(collect($request));

            return $this->sendSuccess($result, 'Main categories retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve main categories: '.$e->getMessage());
        }
    }
}
