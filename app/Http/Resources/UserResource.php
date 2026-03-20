<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'avatar'      => $this->avatar,
            'is_active'   => $this->is_active,
            'role'        => $this->getRoleNames()->first(),   // Primary role
            'permissions' => $this->permissionNames(),         // Full array for frontend guards
            'tenant'      => [
                'id'          => $this->tenant->id,
                'name'        => $this->tenant->name,
                'slug'        => $this->tenant->slug,
                'clinic_type' => $this->tenant->clinic_type,
            ],
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
