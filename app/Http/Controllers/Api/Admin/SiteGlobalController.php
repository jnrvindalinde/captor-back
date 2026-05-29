<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteGlobalResource;
use App\Models\SiteGlobal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteGlobalController extends Controller
{
    public function show()
    {
        return new SiteGlobalResource(SiteGlobal::current());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name'        => ['nullable', 'string', 'max:255'],
            'tagline_en'          => ['nullable', 'string', 'max:255'],
            'tagline_fr'          => ['nullable', 'string', 'max:255'],
            'logo_light_url'      => ['nullable', 'string', 'max:2048'],
            'logo_dark_url'       => ['nullable', 'string', 'max:2048'],
            'contact_email'       => ['nullable', 'email', 'max:255'],
            'contact_phone'       => ['nullable', 'string', 'max:64'],
            'address_en'          => ['nullable', 'string', 'max:2000'],
            'address_fr'          => ['nullable', 'string', 'max:2000'],
            'socials'             => ['nullable', 'array'],
            'socials.*'           => ['nullable', 'string', 'max:2048'],
            'footer_copyright_en' => ['nullable', 'string', 'max:255'],
            'footer_copyright_fr' => ['nullable', 'string', 'max:255'],
        ]);

        $g = SiteGlobal::current();
        $g->fill($data)->save();
        Cache::forget('cms.globals');

        return new SiteGlobalResource($g->fresh());
    }
}
