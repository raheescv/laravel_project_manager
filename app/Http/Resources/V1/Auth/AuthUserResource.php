<?php

namespace App\Http\Resources\V1\Auth;

use App\Models\SaleDaySession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $default_branch_id = $this->default_branch_id ? (string) $this->default_branch_id : null;

        $openingSession = SaleDaySession::getOpenSessionForBranch($default_branch_id);
        $lastClosedSession = SaleDaySession::where('branch_id', $default_branch_id)
            ->where('status', 'closed')
            ->orderBy('closed_at', 'desc')
            ->first();
        $date = $openingSession ? $openingSession->opened_at->format('Y-m-d') : now()->format('Y-m-d');

        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'code' => $this->code,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'is_admin' => (bool) $this->is_admin,
            'permissions' => $this->getAllPermissions()->pluck('name')->values(),
            'designation' => $this->designation?->name,
            'branch_id' => $default_branch_id,
            'sale_day_session_date' => $date,
            'sale_day_session_status' => $openingSession ? 'open' : 'closed',
            'sale_day_session_opened_at' => $openingSession?->opened_at?->format('Y-m-d H:i:s'),
            'last_closed_session_at' => $lastClosedSession?->closed_at?->format('Y-m-d H:i:s'),
        ];
    }
}
