<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteGlobalResource;
use App\Models\SiteGlobal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicSiteGlobalController extends Controller
{
    public function show(): JsonResponse
    {
        $payload = Cache::remember('cms.globals', 300, function () {
            return (new SiteGlobalResource(SiteGlobal::current()))->resolve();
        });

        return response()->json(['data' => $payload])
            ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=600');
    }
}
