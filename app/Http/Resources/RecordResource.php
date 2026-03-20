<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'client_id'        => $this->client_id,
            'template_key'     => $this->template_key,
            'template_version' => $this->template_version,
            'data'             => $this->data,
            'notes'            => $this->notes,
            'status'           => $this->status,
            'recorded_at'      => $this->recorded_at?->toISOString(),
            'created_by'       => $this->whenLoaded('creator', fn () => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'reviewed_by'      => $this->whenLoaded('reviewer', fn () => $this->reviewer ? [
                'id'          => $this->reviewer->id,
                'name'        => $this->reviewer->name,
                'reviewed_at' => $this->reviewed_at?->toISOString(),
            ] : null),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
