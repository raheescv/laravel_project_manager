<?php

namespace App\Http\Resources\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'alias_name' => $this->alias_name,
            'mobile' => $this->mobile,
            'whatsapp_mobile' => $this->whatsapp_mobile,
            'email' => $this->email,
            'place' => $this->place,
            'gst_no' => $this->gst_no,
        ];
    }
}
