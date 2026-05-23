<?php

namespace App\Http\Resources\V1\Auth;

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
        return [
            'id' => (string) $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'code' => $this->code,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'is_admin' => (bool) $this->is_admin,
            'designation' => $this->designation?->name,
            'branch_id' => $this->default_branch_id ? (string) $this->default_branch_id : null,
        ];
    }
}
