<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\DaySession\ToggleStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\DaySession\StatusRequest;
use App\Http\Requests\V1\DaySession\ToggleRequest;
use App\Http\Resources\V1\DaySession\DaySessionResource;
use App\Models\SaleDaySession;
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
     * the open session if one exists. The branch is the one the app is
     * operating as (`branch_id`), falling back to the user's default branch.
     * Requires a `date` field — when closing it must be on or after the
     * session's opened_at.
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
     * Returns whether the branch the app is operating as (`branch_id`, falling
     * back to the user's default branch) currently has an open day session,
     * the open session's details when one exists, and the moment of the most
     * recent close — enough for a client to refresh its cached day-session
     * block without signing in again.
     */
    public function status(StatusRequest $request): JsonResponse
    {
        try {
            $branchId = $request->branchId();

            if (! $branchId) {
                return $this->sendError('No default branch assigned to this user.');
            }

            $session = SaleDaySession::getOpenSessionForBranch($branchId);
            $isOpen = $session !== null;

            if ($session) {
                $session->load(['opener:id,name', 'closer:id,name', 'branch']);
            }

            // The most recent close, so a client refreshing while the day is
            // shut can still show "Last closed …" — the open session alone
            // leaves it with nothing to render.
            $lastClosed = SaleDaySession::where('branch_id', $branchId)
                ->where('status', 'closed')
                ->orderBy('closed_at', 'desc')
                ->first();

            return $this->sendSuccess([
                'is_open' => $isOpen,
                'status' => $isOpen ? 'open' : 'closed',
                // Same shape as AuthUserResource's day-session block, so a
                // client can refresh its cached user straight from this.
                'date' => $isOpen ? $session->opened_at->format('Y-m-d') : now()->format('Y-m-d'),
                'opened_at' => $session?->opened_at?->format('Y-m-d H:i:s'),
                'last_closed_at' => $lastClosed?->closed_at?->format('Y-m-d H:i:s'),
                // Opening/closing/expected float belongs to whoever runs the
                // day, so the session block is permission-gated even though the
                // status above it is not.
                'session' => $session && $request->user()->can('day session.create')
                    ? new DaySessionResource($session)
                    : null,
            ], $isOpen ? 'Day is open' : 'Day is closed');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to check day status: '.$e->getMessage());
        }
    }
}
