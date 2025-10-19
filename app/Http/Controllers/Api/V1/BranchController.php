<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Branch\GetBranchesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\GetBranchesRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

#[Group('Product Filter')]
class BranchController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of branches with optional filtering.
     *
     * @queryParam query string Filter branches by name, code, or location (partial match). Example: Main
     * @queryParam user_id integer Filter branches assigned to a specific user. Example: 1
     * @queryParam assigned_only boolean Filter only branches assigned to the user. Example: true
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Main Branch",
     *       "code": "MB001",
     *       "location": "Downtown",
     *       "mobile": "+1234567890"
     *     }
     *   ],
     *   "message": "Branches retrieved successfully"
     * }
     */
    public function index(GetBranchesAction $action, GetBranchesRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Branches retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve branches: '.$e->getMessage());
        }
    }
}
