<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\V1\DaySession\ReportAction;
use App\Actions\V1\DaySession\ToggleStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\DaySession\StatusRequest;
use App\Http\Requests\V1\DaySession\ToggleRequest;
use App\Http\Resources\V1\DaySession\DaySessionResource;
use App\Models\SaleDaySession;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * List the branch's day sessions.
     *
     * The operating branch's sessions (`branch_id`, falling back to the user's
     * default branch), newest first, 20 a page — what the app lists to pick a
     * session's Sale Bill Report from.
     */
    public function index(ReportAction $action, StatusRequest $request): JsonResponse
    {
        try {
            $branchId = $request->branchId();

            if (! $branchId) {
                return $this->sendError('No default branch assigned to this user.');
            }

            $sessions = SaleDaySession::with(['branch', 'opener:id,name', 'closer:id,name'])
                ->where('branch_id', $branchId)
                ->orderByDesc('opened_at')
                ->orderByDesc('id')
                ->paginate(20);

            return $this->sendSuccess([
                'data' => $sessions->getCollection()->map(fn (SaleDaySession $session) => $action->row($session))->values(),
                'pagination' => [
                    'current_page' => $sessions->currentPage(),
                    'last_page' => $sessions->lastPage(),
                    'per_page' => $sessions->perPage(),
                    'total' => $sessions->total(),
                ],
            ], 'Day sessions retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendServerError('Failed to list day sessions: '.$e->getMessage());
        }
    }

    /**
     * Day session report data.
     *
     * The figures of the web "Sale Bill Report" (print::sale::day-session-report)
     * for one session — transactions with their payments, dues, due payments
     * received and the total summary — for the app to print on a thermal roll.
     */
    public function report(ReportAction $action, int $id): JsonResponse
    {
        try {
            return $this->sendSuccess($action->data($action->find($id)), 'Day session report generated successfully');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Day session not found.');
        } catch (\Throwable $e) {
            Log::error('API v1 day session report failed', ['id' => $id, 'exception' => $e]);

            return $this->sendServerError('Failed to build the day session report: '.$e->getMessage());
        }
    }

    /**
     * Day session report PDF.
     *
     * The web A4 "Sale Bill Report" for one session as `application/pdf` bytes
     * — the same view the back office prints, rendered through Chrome. Errors
     * still answer in the JSON envelope.
     */
    public function reportPdf(ReportAction $action, int $id): Response
    {
        try {
            $session = $action->find($id);

            return response($action->pdf($session))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="day-session-'.$session->id.'.pdf"');
        } catch (ModelNotFoundException) {
            return $this->sendNotFoundError('Day session not found.');
        } catch (\Throwable $e) {
            Log::error('API v1 day session report PDF failed', ['id' => $id, 'exception' => $e]);

            return $this->sendServerError('Failed to render the day session report PDF: '.$e->getMessage());
        }
    }
}
