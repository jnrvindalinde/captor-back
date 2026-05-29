<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicPageController extends Controller
{
    /**
     * GET /api/public/pages/{slug}
     * Returns the published page with published sections only.
     */
    public function show(string $slug): JsonResponse
    {
        $payload = Cache::remember("cms.page.{$slug}", 300, function () use ($slug) {
            $page = Page::where('slug', $slug)
                ->where('status', Page::STATUS_PUBLISHED)
                ->with([
                    'publishedSections' => fn ($q) => $q->orderBy('position'),
                    'ogImage',
                ])
                ->first();

            if (! $page) return null;

            // Hydrate as PageResource manually to ensure sections come from the
            // publishedSections relation.
            $page->setRelation('sections', $page->publishedSections);

            return (new PageResource($page))->resolve();
        });

        if ($payload === null) {
            return response()->json(['message' => 'Page not found.'], 404);
        }

        return response()->json(['data' => $payload])
            ->header('Cache-Control', 'public, max-age=60, stale-while-revalidate=600');
    }

    /**
     * GET /api/public/pages/{slug}/preview/{token}
     *
     * Returns the page regardless of status (draft or published) including
     * draft sections, when the token matches the page's preview token.
     * Always bypasses cache and responds with no-store to keep drafts off CDNs.
     */
    public function preview(string $slug, string $token): JsonResponse
    {
        $page = Page::where('slug', $slug)
            ->with(['sections' => fn ($q) => $q->orderBy('position'), 'ogImage'])
            ->first();

        if (! $page || ! hash_equals($page->previewToken(), $token)) {
            return response()->json(['message' => 'Preview not found.'], 404);
        }

        $payload = (new PageResource($page))->resolve();

        return response()->json(['data' => $payload])
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
