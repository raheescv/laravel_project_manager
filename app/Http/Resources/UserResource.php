<?php

namespace App\Http\Resources;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->createToken('astra-auth-token')->plainTextToken,
            'id' => (string) $this->id,
            'type' => $this->type,
            'type' => 'admin',
            'user_type' => $this->type,
            'user_type' => 'admin',
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'is_admin' => (bool) $this->is_admin,
            'branch_id' => (string) $this->default_branch_id,
            'invoice_counts' => Sale::count(),
            // 'is_active' => (bool) $this->is_active,
            // 'branch' => $this->when($this->branch, [
            //     'id' => $this->branch?->id,
            //     'name' => $this->branch?->name,
            //     'code' => $this->branch?->code,
            // ]),
            // 'roles' => $this->roles->pluck('name'),
        ];
    }
}
