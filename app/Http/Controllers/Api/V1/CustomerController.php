<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Customer\ListAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Customer\IndexRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

#[Group('Mobile - Customers')]
class CustomerController extends Controller
{
    use ApiResponseTrait;

    /**
     * Search customers.
     *
     * Returns customers matching the given mobile number (partial match) and/or search term.
     * Use this for customer lookup by phone from the mobile app.
     */
    public function index(ListAction $action, IndexRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Customers retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve customers: '.$e->getMessage());
        }
    }
}
