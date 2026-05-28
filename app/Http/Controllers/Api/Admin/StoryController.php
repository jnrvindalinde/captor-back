<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'status'   => ['nullable', Rule::in([Story::STATUS_DRAFT, Story::STATUS_PUBLISHED])],
            'outcome'  => ['nullable', Rule::in(Story::OUTCOMES)],
            'q'        => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Story::query()->with('author:id,name,email')->latest();
        if (! empty($params['status'])) $query->where('status', $params['status']);
        if (! empty($params['outcome'])) $query->where('outcome', $params['outcome']);
        if (! empty($params['q'])) {
            $term = '%'.$params['q'].'%';
            $query->where(fn ($q) => $q->where('title', 'ilike', $term)
                ->orWhere('person_name', 'ilike', $term)
                ->orWhere('slug', 'ilike', $term));
        }

        return response()->json($query->paginate($params['per_page'] ?? 25));
    }

    public function show(Story $story): JsonResponse
    {
        return response()->json(['story' => $story->load('author:id,name,email')]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['title']);
        $data['author_id'] = $request->user()->id;
        $story = Story::create($data);
        return response()->json(['story' => $story], 201);
    }

    public function update(Request $request, Story $story): JsonResponse
    {
        $data = $this->validatePayload($request, $story);
        if (! empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $data['title'] ?? $story->title, $story->id);
        }
        $story->fill($data)->save();
        return response()->json(['story' => $story->fresh()]);
    }

    public function destroy(Story $story): JsonResponse
    {
        $story->delete();
        return response()->json(null, 204);
    }

    private function validatePayload(Request $request, ?Story $story = null): array
    {
        return $request->validate([
            'title'        => ['required', 'string', 'max:200'],
            'slug'         => ['nullable', 'string', 'max:220'],
            'summary'      => ['nullable', 'string', 'max:500'],
            'body'         => ['nullable', 'string'],
            'person_name'  => ['required', 'string', 'max:120'],
            'person_role'  => ['nullable', 'string', 'max:120'],
            'outcome'      => ['nullable', Rule::in(Story::OUTCOMES)],
            'cover_image'  => ['nullable', 'string', 'max:500'],
            'status'       => ['required', Rule::in([Story::STATUS_DRAFT, Story::STATUS_PUBLISHED])],
        ]);
    }

    private function uniqueSlug(?string $candidate, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($candidate ?: $title);
        $slug = $base;
        $i = 1;
        while (Story::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
