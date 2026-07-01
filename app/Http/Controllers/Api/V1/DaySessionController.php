<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\DaySession\ToggleStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\DaySession\ToggleRequest;
use App\Http\Resources\V1\DaySession\DaySessionResource;
use App\Models\SaleDaySession;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    /**
     * Check the current day session status.
     *
     * Returns whether the authenticated user's default branch currently has an
     * open day session, along with the open session's details when one exists.
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $branchId = $request->user()?->default_branch_id;

            if (! $branchId) {
                return $this->sendError('No default branch assigned to this user.');
            }

            $session = SaleDaySession::getOpenSessionForBranch($branchId);
            $isOpen = $session !== null;

            if ($session) {
                $session->load(['opener:id,name', 'closer:id,name', 'branch']);
            }

            return $this->sendSuccess([
                'is_open' => $isOpen,
                'status' => $isOpen ? 'open' : 'closed',
                'session' => $session ? new DaySessionResource($session) : null,
            ], $isOpen ? 'Day is open' : 'Day is closed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to check day status: '.$e->getMessage());
        }
    }
}
