<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Dashboard\GetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Dashboard\IndexRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

#[Group('Mobile - Admin')]
class DashboardController extends Controller
{
    use ApiResponseTrait;

    /**
     * Fetch overview dashboard.
     *
     * Returns today's snapshot cards plus the sales-overview block (Sales Performance
     * and Payment Overview) matching the /report/sales_overview screen. Accepts
     * optional from_date, to_date and branch_id filters (default: today, all branches).
     */
    public function index(GetAction $action, IndexRequest $request): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute($request), 'Dashboard retrieved successfully');
        } catch (ValidationException $e) {
            return $this->sendValidationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve dashboard: '.$e->getMessage());
        }
    }
}
