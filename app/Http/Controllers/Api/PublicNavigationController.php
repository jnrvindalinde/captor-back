<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NavigationMenuResource;
use App\Models\NavigationMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicNavigationController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $payload = Cache::remember("cms.menu.{$slug}", 300, function () use ($slug) {
            $menu = NavigationMenu::where('slug', $slug)
                ->with(['items' => fn ($q) => $q->where('visible', true)->orderBy('sort_order')])
                ->first();

            if (! $menu) return null;
            return (new NavigationMenuResource($menu))->resolve();
        });

        if ($payload === null) {
            return response()->json(['message' => 'Menu not found.'], 404);
        }

        return response()->json(['data' => $payload])
            ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=600');
    }
}
