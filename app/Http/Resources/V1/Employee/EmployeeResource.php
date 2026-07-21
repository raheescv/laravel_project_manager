<?php

namespace App\Http\Resources\V1\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'mobile' => $this->mobile,
            // Root-relative storage path; the mobile client prepends its own base
            // URL (see AuthUserResource for the same convention).
            'photo' => $this->image ? '/storage/'.ltrim($this->image, '/') : null,
            'designation' => $this->whenLoaded('designation', fn () => $this->designation?->name),
        ];
    }
}
