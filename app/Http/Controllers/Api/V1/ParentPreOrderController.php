<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Parent\FindStudentAction;
use App\Actions\Student\PreOrder\MenuAction;
use App\Actions\Student\PreOrder\SaveAction;
use App\Actions\Student\PreOrder\ScheduleAction;
use App\Actions\Student\PreOrder\SetDayAction;
use App\Actions\Student\PreOrder\SetWeeklyStatusAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Parent\SavePreOrderRequest;
use App\Models\Guardian;
use App\Models\StudentPreOrder;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Canteen pre-orders in the parent portal (routes/api_v1_parent.php).
 *
 * A pre-order is items only: when the child's card is tapped, QLOUD POS puts the
 * day's items in the cart and the card sale charges them. Every student is
 * reached through the signed-in parent, so another family's child is a 404.
 */
#[Group('Parent Portal - Pre-orders')]
class ParentPreOrderController extends Controller
{
    use ApiResponseTrait;

    /**
     * Pre-order menu.
     *
     * Selling products in the categories the school put on the menu, grouped by
     * category, at today's price. Empty when pre-orders are switched off.
     */
    public function menu(): JsonResponse
    {
        try {
            return $this->sendSuccess((new MenuAction())->execute(), 'Menu retrieved successfully');
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not load the menu. Please try again.');
        }
    }

    /**
     * A child's pre-orders.
     *
     * The weekly order and what happens on each of the next school days
     * (`source`: day · weekly · skipped · null). `locked` days can no longer be
     * changed (the school's cut-off has passed).
     */
    public function schedule(Request $request, int $account): JsonResponse
    {
        return $this->attempt(function () use ($request, $account) {
            $student = $this->student($request, $account);

            return $this->sendSuccess((new ScheduleAction())->execute($student->id), 'Pre-orders retrieved successfully');
        });
    }

    /**
     * Save a day's order.
     *
     * Replaces that day's order (or its skip). The weekly order does not apply on
     * a day with its own order.
     */
    public function saveDay(SavePreOrderRequest $request, int $account, string $date): JsonResponse
    {
        return $this->attempt(function () use ($request, $account, $date) {
            $student = $this->student($request, $account);

            return $this->respond((new SaveAction())->execute($student, $this->guardian($request), [
                'type' => StudentPreOrder::TYPE_DAY,
                'date' => $date,
                'items' => $request->validated('items'),
                'note' => $request->validated('note'),
            ]), $student->id);
        });
    }

    /**
     * Skip a day.
     *
     * Nothing is added at the till that day, even when the weekly order covers it.
     */
    public function skipDay(Request $request, int $account, string $date): JsonResponse
    {
        return $this->attempt(function () use ($request, $account, $date) {
            $student = $this->student($request, $account);

            return $this->respond((new SetDayAction())->execute($student, $this->guardian($request), $date, SetDayAction::SKIP), $student->id);
        });
    }

    /**
     * Remove a day's order or skip.
     *
     * The weekly order (if any) applies to that day again.
     */
    public function clearDay(Request $request, int $account, string $date): JsonResponse
    {
        return $this->attempt(function () use ($request, $account, $date) {
            $student = $this->student($request, $account);

            return $this->respond((new SetDayAction())->execute($student, $this->guardian($request), $date, SetDayAction::CLEAR), $student->id);
        });
    }

    /**
     * Save the weekly order.
     *
     * The items added on the chosen school days (`weekdays`, ISO 1–7). Takes
     * effect from the next tap. A paused weekly order stays paused.
     */
    public function saveWeekly(SavePreOrderRequest $request, int $account): JsonResponse
    {
        return $this->attempt(function () use ($request, $account) {
            $student = $this->student($request, $account);

            return $this->respond((new SaveAction())->execute($student, $this->guardian($request), [
                'type' => StudentPreOrder::TYPE_WEEKLY,
                'weekdays' => $request->validated('weekdays') ?? [],
                'items' => $request->validated('items'),
                'note' => $request->validated('note'),
            ]), $student->id);
        });
    }

    /** Pause the weekly order. */
    public function pauseWeekly(Request $request, int $account): JsonResponse
    {
        return $this->weeklyStatus($request, $account, StudentPreOrder::STATUS_PAUSED);
    }

    /** Switch the weekly order back on. */
    public function resumeWeekly(Request $request, int $account): JsonResponse
    {
        return $this->weeklyStatus($request, $account, StudentPreOrder::STATUS_ACTIVE);
    }

    /** Remove the weekly order. */
    public function deleteWeekly(Request $request, int $account): JsonResponse
    {
        return $this->weeklyStatus($request, $account, StudentPreOrder::STATUS_CANCELLED);
    }

    private function weeklyStatus(Request $request, int $account, string $status): JsonResponse
    {
        return $this->attempt(function () use ($request, $account, $status) {
            $student = $this->student($request, $account);

            return $this->respond((new SetWeeklyStatusAction())->execute($student, $this->guardian($request), $status), $student->id);
        });
    }

    /** An action result → the refreshed schedule, or its message as a 422. */
    private function respond(array $result, int $accountId): JsonResponse
    {
        if (! $result['success']) {
            return $this->sendError($result['message'], [], 422);
        }

        return $this->sendSuccess((new ScheduleAction())->execute($accountId), $result['message']);
    }

    private function attempt(callable $callback): JsonResponse
    {
        try {
            return $callback();
        } catch (NotFoundHttpException) {
            return $this->sendNotFoundError('Student not found');
        } catch (\Throwable $e) {
            return $this->failure($e, 'We could not save the pre-order. Please try again.');
        }
    }

    private function student(Request $request, int $account)
    {
        return (new FindStudentAction())->execute($this->guardian($request), $account);
    }

    private function guardian(Request $request): Guardian
    {
        return $request->user('parent');
    }

    private function failure(\Throwable $e, string $message): JsonResponse
    {
        report($e);

        return $this->sendServerError($message);
    }
}
