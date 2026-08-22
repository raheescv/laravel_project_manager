<?php

namespace App\Actions\PropertyAppointment;

use App\Exceptions\PropertyAppointment\SlotUnavailableException;
use App\Models\PropertyAppointment;
use App\Services\PropertyAppointment\SlotService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Commits a customer's slot choice.
 *
 * This action SELF-TRANSACTS: its caller is a public, unauthenticated endpoint
 * serving one request = one appointment, so a caller-level boundary would be pure
 * ceremony. The public Livewire component must therefore NOT wrap it in a
 * transaction of its own.
 */
class BookAction
{
    /** MySQL duplicate-key error. */
    private const DUPLICATE_ENTRY = 1062;

    /**
     * @param  mixed  $endsAt  When the customer leaves. Null keeps the configured
     *                         slot length, which is what a tapped preset means.
     */
    public function execute($appointmentId, $scheduledAt, $bookedBy = 'customer', $userId = null, $timezone = null, $endsAt = null)
    {
        try {
            $slot = Carbon::parse($scheduledAt);
            $end = $endsAt
                ? Carbon::parse($endsAt)
                : $slot->copy()->addMinutes(SlotService::slotLengthMinutes());
            $wasReschedule = false;

            $return = DB::transaction(function () use ($appointmentId, $slot, $end, $bookedBy, $userId, $timezone, &$wasReschedule) {
                // Lock the row so two requests for the SAME appointment serialise.
                // Two requests for two DIFFERENT appointments contending for one
                // slot are not covered by this lock — the unique index below is
                // what stops them, which is why the insert is still guarded.
                $model = PropertyAppointment::query()->lockForUpdate()->findOrFail($appointmentId);

                if (! $model->isLinkUsable()) {
                    throw new \Exception('This appointment link is no longer valid. Please contact us to arrange your appointment.', 1);
                }

                if ($model->status === 'scheduled' && $model->scheduled_at?->equalTo($slot) && $model->endsAt()?->equalTo($end)) {
                    return ['success' => true, 'message' => 'This appointment is already booked for that time.', 'data' => $model];
                }

                // One gate for both ways in: a tapped preset is a window someone
                // filled in for the customer, so it is checked exactly as a typed
                // one is — inside the open hours, clear of everything already on
                // the calendar, and past the notice cut-off.
                $problem = app(SlotService::class)->windowProblem($model->employee_id, $slot, $end, $model->id);

                if (! $problem['ok']) {
                    throw new SlotUnavailableException($problem['reason'], $problem['taken'] ? 1 : 0);
                }

                // A CONFIRMED appointment moving to a different time is a
                // reschedule, and the customer is told. A first booking is not:
                // nothing they were promised has changed.
                $wasReschedule = $model->status === 'scheduled' && $model->scheduled_at !== null;

                $model->update([
                    'scheduled_at' => $slot,
                    'ends_at' => $end,
                    'status' => 'scheduled',
                    'booked_at' => now(),
                    'booked_by' => $bookedBy,
                    'customer_timezone' => $timezone,
                    'cancelled_at' => null,
                    'cancelled_by' => null,
                    'cancel_reason' => null,
                    // The new time deserves its own reminder.
                    'reminder_sent_at' => null,
                    'updated_by' => $userId,
                ]);

                return ['success' => true, 'message' => 'Your appointment is confirmed.', 'data' => $model->fresh()];
            });
        } catch (QueryException $e) {
            // The database rejected the write because another request took this
            // exact slot first. This is the authoritative answer — the check
            // above can always go stale between SELECT and UPDATE.
            if ((int) ($e->errorInfo[1] ?? 0) === self::DUPLICATE_ENTRY) {
                return [
                    'success' => false,
                    'message' => 'Sorry — that slot was just booked by someone else. Please choose another time.',
                    'slot_taken' => true,
                ];
            }

            return ['success' => false, 'message' => $e->getMessage()];
        } catch (SlotUnavailableException $e) {
            // Only a clash frees the page to say "someone else took it" and
            // re-render the grid; a window outside the open hours is the
            // customer's own input to fix.
            return ['success' => false, 'message' => $e->getMessage(), 'slot_taken' => $e->getCode() === 1];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }

        if (($return['success'] ?? false) && $wasReschedule && $return['data'] instanceof PropertyAppointment) {
            // After the commit, and never able to fail the booking itself.
            (new NotifyAction())->execute($return['data'], 'appointment_rescheduled', $userId);
        }

        return $return;
    }
}
