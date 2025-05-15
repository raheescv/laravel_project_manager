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
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'code' => $this->code,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'is_admin' => (bool) $this->is_admin,
            'branch_id' => $this->default_branch_id,
            'invoiceCounts' => Sale::count(),
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
