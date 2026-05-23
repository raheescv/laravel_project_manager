<?php

namespace App\Actions\V1\Day;

use App\Http\Requests\V1\Day\StatusRequest;
use App\Http\Resources\V1\Day\DaySessionResource;
use App\Models\SaleDaySession;

class GetStatusAction
{
    /**
     * Retrieve day open/close sessions opened within the requested date range.
     */
    public function execute(StatusRequest $request): array
    {
        $openDate = $request->validated('openDate');
        $closingDate = $request->validated('closingDate');

        $sessions = SaleDaySession::query()
            ->with(['opener:id,name', 'closer:id,name', 'branch'])
            ->whereDate('opened_at', '>=', $openDate)
            ->whereDate('opened_at', '<=', $closingDate)
            ->orderByDesc('opened_at')
            ->get();

        return [
            'count' => $sessions->count(),
            'sessions' => DaySessionResource::collection($sessions),
        ];
    }
}
