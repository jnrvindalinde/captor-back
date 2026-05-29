<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NavigationItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\NavigationItem $this */
        return [
            'uuid'       => $this->uuid,
            'label'      => ['en' => $this->label_en, 'fr' => $this->label_fr],
            'href'       => $this->href,
            'target'     => $this->target,
            'sort_order' => $this->sort_order,
            'visible'    => $this->visible,
        ];
    }
}
