<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\Day\GetStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Day\StatusRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Mobile - Admin')]
class DayController extends Controller
{
    use ApiResponseTrait;

    /**
     * Fetch day open/close status.
     *
     * Retrieves the opening and closing timestamps of day sessions within a date range.
     */
    public function status(GetStatusAction $action, StatusRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, 'Day status retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to retrieve day status: '.$e->getMessage());
        }
    }
}
