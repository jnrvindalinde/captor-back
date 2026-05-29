<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicCollectionController extends Controller
{
    /**
     * GET /api/public/collections/{slug}
     * Returns published items in position order. Cached for 5 minutes.
     */
    public function show(string $slug): JsonResponse
    {
        // NOTE: cache tags require redis/memcached. We use a plain key here
        // and bust it explicitly from the admin controller on writes.
        $payload = Cache::remember(
            "cms.collection.{$slug}",
            300,
            function () use ($slug) {
                $collection = Collection::where('slug', $slug)->first();
                if (! $collection) return null;

                $items = $collection->publishedItems()->get()->map(fn ($i) => [
                    'uuid' => $i->uuid,
                    'data' => $i->data,
                ])->values();

                return [
                    'slug'  => $collection->slug,
                    'name'  => $collection->name,
                    'items' => $items,
                ];
            },
        );

        if ($payload === null) {
            return response()->json(['message' => 'Collection not found.'], 404);
        }

        return response()->json($payload)
            ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=600');
    }
}
