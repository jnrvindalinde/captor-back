<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'status'   => ['nullable', Rule::in([Post::STATUS_DRAFT, Post::STATUS_PUBLISHED])],
            'q'        => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Post::query()
            ->with('author:id,name,email')
            ->latest();

        if (! empty($params['status'])) $query->where('status', $params['status']);
        if (! empty($params['q'])) {
            $term = '%'.$params['q'].'%';
            $query->where(fn ($q) => $q->where('title', 'ilike', $term)->orWhere('slug', 'ilike', $term));
        }

        return response()->json($query->paginate($params['per_page'] ?? 25));
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json(['post' => $post->load('author:id,name,email')]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['title']);
        $data['author_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? Post::STATUS_PUBLISHED;
        if ($data['status'] === Post::STATUS_PUBLISHED && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $post = Post::create($data);
        return response()->json(['post' => $post], 201);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $data = $this->validatePayload($request, $post);
        if (! empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $data['title'] ?? $post->title, $post->id);
        }
        if (($data['status'] ?? $post->status) === Post::STATUS_PUBLISHED && ! $post->published_at) {
            $data['published_at'] = $data['published_at'] ?? now();
        }
        $post->fill($data)->save();
        return response()->json(['post' => $post->fresh()]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();
        return response()->json(null, 204);
    }

    private function validatePayload(Request $request, ?Post $post = null): array
    {
        return $request->validate([
            'title'        => ['required', 'string', 'max:200'],
            'slug'         => ['nullable', 'string', 'max:220'],
            'excerpt'      => ['nullable', 'string', 'max:500'],
            'body'         => ['nullable', 'string'],
            'cover_image'  => ['nullable', 'string', 'max:1000'],
            'status'       => ['nullable', Rule::in([Post::STATUS_DRAFT, Post::STATUS_PUBLISHED])],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['string', 'max:40'],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function uniqueSlug(?string $candidate, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($candidate ?: $title);
        $slug = $base;
        $i = 1;
        while (Post::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
