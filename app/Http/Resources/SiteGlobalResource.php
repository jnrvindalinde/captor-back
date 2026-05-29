<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteGlobalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\SiteGlobal $this */
        return [
            'company_name'    => $this->company_name,
            'tagline'         => ['en' => $this->tagline_en, 'fr' => $this->tagline_fr],
            'logo'            => ['light' => $this->logo_light_url, 'dark' => $this->logo_dark_url],
            'contact'         => [
                'email' => $this->contact_email,
                'phone' => $this->contact_phone,
            ],
            'address'         => ['en' => $this->address_en, 'fr' => $this->address_fr],
            'socials'         => $this->socials ?? [],
            'footer_copyright'=> ['en' => $this->footer_copyright_en, 'fr' => $this->footer_copyright_fr],
        ];
    }
}
