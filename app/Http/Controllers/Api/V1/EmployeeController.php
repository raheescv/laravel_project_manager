<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Employee\ListAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Employee\IndexRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

#[Group('Mobile - Employees')]
class EmployeeController extends Controller
{
    use ApiResponseTrait;

    /**
     * List employees (stylists).
     *
     * Returns active staff (type = employee) for assigning a stylist to a sale
     * or a sale line in the mobile POS. Supports an optional search term and a
     * branch_id filter.
     */
    public function index(ListAction $action, IndexRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Employees retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve employees: '.$e->getMessage());
        }
    }
}
