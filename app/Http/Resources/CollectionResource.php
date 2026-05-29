<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Collection $this */
        return [
            'id'          => $this->id,
            'slug'        => $this->slug,
            'name'        => $this->name,
            'description' => $this->description,
            'schema'      => $this->schema,
            'items_count' => $this->whenCounted('items'),
            'items'       => CollectionItemResource::collection($this->whenLoaded('items')),
            'updated_at'  => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
