<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NavigationMenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\NavigationMenu $this */
        return [
            'slug'        => $this->slug,
            'name'        => $this->name,
            'description' => $this->description,
            'items'       => NavigationItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
