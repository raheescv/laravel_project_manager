<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Dashboard\GetAction;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Mobile - Admin')]
class DashboardController extends Controller
{
    use ApiResponseTrait;

    /**
     * Fetch overview dashboard.
     *
     * Returns summary counts and titles for the admin dashboard.
     */
    public function index(GetAction $action): JsonResponse
    {
        try {
            return $this->sendSuccess($action->execute(), 'Dashboard retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve dashboard: '.$e->getMessage());
        }
    }
}
