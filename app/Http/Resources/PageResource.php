<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Page $this */
        return [
            'id'         => $this->id,
            'uuid'       => $this->uuid,
            'slug'       => $this->slug,
            'kind'       => $this->kind,
            'status'     => $this->status,
            'title'      => ['en' => $this->title_en, 'fr' => $this->title_fr],
            'seo'        => [
                'title'       => ['en' => $this->seo_title_en, 'fr' => $this->seo_title_fr],
                'description' => ['en' => $this->seo_description_en, 'fr' => $this->seo_description_fr],
            ],
            'og_image'    => $this->whenLoaded('ogImage', fn () => $this->ogImage ? new MediaResource($this->ogImage) : null),
            'sections'    => PageSectionResource::collection($this->whenLoaded('sections')),
            'preview_token' => $this->when($request->user() !== null, fn () => $this->previewToken()),
            'published_at' => optional($this->published_at)->toIso8601String(),
            'updated_at'   => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
