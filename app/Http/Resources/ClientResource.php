<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'full_name'     => $this->full_name,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age'           => $this->age,
            'gender'        => $this->gender,
            'address'       => [
                'line'        => $this->address,
                'city'        => $this->city,
                'state'       => $this->state,
                'country'     => $this->country,
                'postal_code' => $this->postal_code,
            ],
            'emergency_contact' => [
                'name'         => $this->emergency_contact_name,
                'phone'        => $this->emergency_contact_phone,
                'relationship' => $this->emergency_contact_relationship,
            ],
            'status'        => $this->status,
            'metadata'      => $this->metadata,
            'created_by'    => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'records_count' => $this->whenLoaded('records', fn () => $this->records->count()),
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}
