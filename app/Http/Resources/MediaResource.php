<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Media $this */
        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'provider'          => $this->provider,
            'public_id'         => $this->public_id,
            'url'               => $this->secure_url,
            'format'            => $this->format,
            'width'             => $this->width,
            'height'            => $this->height,
            'bytes'             => $this->bytes,
            'original_filename' => $this->original_filename,
            'folder'            => $this->folder,
            'alt'               => [
                'en' => $this->alt_en,
                'fr' => $this->alt_fr,
            ],
            'caption' => [
                'en' => $this->caption_en,
                'fr' => $this->caption_fr,
            ],
            'meta'       => $this->meta,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
