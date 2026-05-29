<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'q'        => ['nullable', 'string', 'max:120'],
            'tag'      => ['nullable', 'string', 'max:60'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Post::query()
            ->with('author:id,name,email')
            ->where('status', Post::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');

        if (! empty($params['q'])) {
            $term = '%'.$params['q'].'%';
            $query->where(fn ($q) => $q->where('title', 'ilike', $term)
                ->orWhere('excerpt', 'ilike', $term));
        }

        if (! empty($params['tag'])) {
            $query->whereJsonContains('tags', $params['tag']);
        }

        return response()->json($query->paginate($params['per_page'] ?? 24))
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300');
    }

    public function show(string $slug): JsonResponse
    {
        $post = Post::query()
            ->with('author:id,name,email')
            ->where('slug', $slug)
            ->where('status', Post::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        return response()->json(['post' => $post])
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300');
    }
}
