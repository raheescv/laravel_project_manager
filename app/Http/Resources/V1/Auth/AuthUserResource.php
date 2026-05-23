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
        $date = $openingSession ? $openingSession->opened_at->format('Y-m-d') : now()->format('Y-m-d');;

        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'code' => $this->code,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'is_admin' => (bool) $this->is_admin,
            'designation' => $this->designation?->name,
            'branch_id' => $default_branch_id,
            'sale_day_session_date' => $date,
        ];
    }
}
