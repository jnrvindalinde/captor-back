<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ResourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'status'   => ['nullable', Rule::in([Resource::STATUS_DRAFT, Resource::STATUS_PUBLISHED])],
            'format'   => ['nullable', Rule::in(Resource::FORMATS)],
            'q'        => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Resource::query()->with('author:id,name,email')->latest();
        if (! empty($params['status'])) $query->where('status', $params['status']);
        if (! empty($params['format'])) $query->where('format', $params['format']);
        if (! empty($params['q'])) {
            $term = '%'.$params['q'].'%';
            $query->where(fn ($q) => $q->where('title', 'ilike', $term)->orWhere('slug', 'ilike', $term));
        }

        return response()->json($query->paginate($params['per_page'] ?? 25));
    }

    public function show(Resource $resource): JsonResponse
    {
        return response()->json(['resource' => $resource->load('author:id,name,email')]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? null, $data['title']);
        $data['author_id'] = $request->user()->id;
        $resource = Resource::create($data);
        return response()->json(['resource' => $resource], 201);
    }

    public function update(Request $request, Resource $resource): JsonResponse
    {
        $data = $this->validatePayload($request, $resource);
        if (! empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $data['title'] ?? $resource->title, $resource->id);
        }
        $resource->fill($data)->save();
        return response()->json(['resource' => $resource->fresh()]);
    }

    public function destroy(Resource $resource): JsonResponse
    {
        $resource->delete();
        return response()->json(null, 204);
    }

    private function validatePayload(Request $request, ?Resource $resource = null): array
    {
        return $request->validate([
            'title'        => ['required', 'string', 'max:200'],
            'slug'         => ['nullable', 'string', 'max:220'],
            'description'  => ['nullable', 'string', 'max:5000'],
            'format'       => ['required', Rule::in(Resource::FORMATS)],
            'file_path'    => ['nullable', 'string', 'max:500'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'cover_image'  => ['nullable', 'string', 'max:500'],
            'status'       => ['required', Rule::in([Resource::STATUS_DRAFT, Resource::STATUS_PUBLISHED])],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['string', 'max:40'],
        ]);
    }

    private function uniqueSlug(?string $candidate, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($candidate ?: $title);
        $slug = $base;
        $i = 1;
        while (Resource::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }
}
