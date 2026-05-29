<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\DaySession\ToggleStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\DaySession\ToggleRequest;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Mobile - Admin')]
class DaySessionController extends Controller
{
    use ApiResponseTrait;

    /**
     * Toggle day open/close status.
     *
     * Opens a new day session if the branch is currently closed, or closes
     * the open session if one exists. Requires a `date` field — when closing
     * it must be on or after the session's opened_at.
     */
    public function toggle(ToggleStatusAction $action, ToggleRequest $request): JsonResponse
    {
        try {
            $result = $action->execute($request);

            return $this->sendSuccess($result, $result['message']);
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to toggle day status: '.$e->getMessage());
        }
    }
}
