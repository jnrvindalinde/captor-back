<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\CollectionItem $this */
        return [
            'id'         => $this->id,
            'uuid'       => $this->uuid,
            'position'   => $this->position,
            'status'     => $this->status,
            'data'       => $this->data,
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
