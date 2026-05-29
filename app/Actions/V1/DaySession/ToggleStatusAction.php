<?php

namespace App\Actions\V1\DaySession;

use App\Http\Requests\V1\DaySession\ToggleRequest;
use App\Http\Resources\V1\DaySession\DaySessionResource;
use App\Models\SaleDaySession;
use Illuminate\Support\Facades\Auth;

class ToggleStatusAction
{
    /**
     * Toggle the day session for the authenticated user's default branch.
     * Opens a new session if currently closed, or closes the open session.
     */
    public function execute(ToggleRequest $request): array
    {
        $branchId = $request->branchId();

        if (! $branchId) {
            throw new \Exception('No default branch assigned to this user.');
        }

        $date = $request->validated('date');

        if ($request->isClosing()) {
            $session = $this->closeSession($request, $date);
            $message = 'Day closed successfully';
        } else {
            $session = $this->openSession($branchId, $date);
            $message = 'Day opened successfully';
        }

        $session->load(['opener:id,name', 'closer:id,name', 'branch']);

        return [
            'message' => $message,
            'status' => $session->status,
            'session' => new DaySessionResource($session),
        ];
    }

    protected function closeSession(ToggleRequest $request, string $date): SaleDaySession
    {
        $session = $request->openSession();
        $session->closed_at = $date;
        $session->closed_by = Auth::id();
        $session->status = 'closed';
        $session->save();

        return $session;
    }

    protected function openSession(int $branchId, string $date): SaleDaySession
    {
        $existingForDate = SaleDaySession::where('branch_id', $branchId)
            ->whereDate('opened_at', $date)
            ->first();

        if ($existingForDate) {
            $existingForDate->update([
                'closed_at' => null,
                'closed_by' => null,
                'status' => 'open',
            ]);

            return $existingForDate;
        }

        return SaleDaySession::create([
            'branch_id' => $branchId,
            'opened_by' => Auth::id(),
            'opened_at' => $date,
            'status' => 'open',
        ]);
    }
}
