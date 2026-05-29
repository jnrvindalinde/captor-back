<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicStoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'q'        => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:60'],
            'outcome'  => ['nullable', 'string', 'max:60'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Story::query()
            ->with('author:id,name,email')
            ->where('status', Story::STATUS_PUBLISHED)
            ->orderByDesc('updated_at');

        if (! empty($params['q'])) {
            $term = '%'.$params['q'].'%';
            $query->where(fn ($q) => $q->where('title', 'ilike', $term)
                ->orWhere('person_name', 'ilike', $term)
                ->orWhere('quote', 'ilike', $term));
        }

        if (! empty($params['category'])) {
            $query->whereJsonContains('categories', $params['category']);
        }

        if (! empty($params['outcome'])) {
            $query->where('outcome', $params['outcome']);
        }

        return response()->json($query->paginate($params['per_page'] ?? 24))
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300');
    }

    public function show(string $slug): JsonResponse
    {
        $story = Story::query()
            ->with('author:id,name,email')
            ->where('slug', $slug)
            ->where('status', Story::STATUS_PUBLISHED)
            ->firstOrFail();

        return response()->json(['story' => $story])
            ->header('Cache-Control', 'public, max-age=60, s-maxage=300');
    }
}
