<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicResourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'q'        => ['nullable', 'string', 'max:120'],
            'tag'      => ['nullable', 'string', 'max:60'],
            'format'   => ['nullable', 'string', 'max:30'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Resource::query()
            ->with('author:id,name,email')
            ->where('status', Resource::STATUS_PUBLISHED)
            ->orderByDesc('updated_at');

        if (! empty($params['q'])) {
            $term = '%'.$params['q'].'%';
            $query->where(fn ($q) => $q->where('title', 'ilike', $term)
                ->orWhere('description', 'ilike', $term));
        }

        if (! empty($params['tag'])) {
            $query->whereJsonContains('tags', $params['tag']);
        }

        if (! empty($params['format'])) {
            $query->where('format', $params['format']);
        }

        return response()->json($query->paginate($params['per_page'] ?? 24))
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300');
    }

    public function show(string $slug): JsonResponse
    {
        $resource = Resource::query()
            ->with('author:id,name,email')
            ->where('slug', $slug)
            ->where('status', Resource::STATUS_PUBLISHED)
            ->firstOrFail();

        return response()->json(['resource' => $resource])
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300');
    }
}
