<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\PageSection $this */
        return [
            'id'         => $this->id,
            'uuid'       => $this->uuid,
            'type'       => $this->type,
            'position'   => $this->position,
            'status'     => $this->status,
            'data'       => $this->data,
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
